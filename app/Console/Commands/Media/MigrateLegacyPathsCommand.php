<?php

namespace App\Console\Commands\Media;

use App\Models\ApplicationSetting;
use App\Models\Branch;
use App\Models\HomeBanner;
use App\Services\Media\MediaUploadService;
use App\Support\MediaUrl;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Signature('media:migrate-legacy-paths {--dry-run : Report actions without attaching media}')]
#[Description('Attach committed public/images seed assets into empty Spatie media collections (data only; no schema changes)')]
class MigrateLegacyPathsCommand extends Command
{
    private const BRANCH_LOGO = '/images/storefront/logo.png';

    private const BANNER_IMAGES = [
        '/images/storefront/hero-1.jpg',
        '/images/storefront/hero2.jpg',
    ];

    public function __construct(private MediaUploadService $media)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $attached = 0;

        foreach (Branch::query()->cursor() as $branch) {
            $attached += $this->attachIfMissing($branch, 'logo', self::BRANCH_LOGO, $dryRun);
        }

        $bannerIndex = 0;

        foreach (HomeBanner::query()->orderBy('sort_order')->orderBy('id')->cursor() as $banner) {
            $path = self::BANNER_IMAGES[$bannerIndex % count(self::BANNER_IMAGES)];
            $attached += $this->attachIfMissing($banner, HomeBanner::IMAGE_COLLECTION, $path, $dryRun);
            $bannerIndex++;
        }

        $settings = ApplicationSetting::query()->first();

        if ($settings !== null) {
            foreach (ApplicationSetting::MEDIA_COLLECTIONS as $collection) {
                if (! $settings->hasMedia($collection)) {
                    $this->line("ApplicationSetting missing {$collection} — upload via platform settings.");
                }
            }
        }

        $verb = $dryRun ? 'Would attach' : 'Attached';
        $this->components->info("{$verb} {$attached} media item(s). Path columns were removed under pre-handover rules; prefer migrate:fresh --seed for greenfield.");

        return self::SUCCESS;
    }

    private function attachIfMissing(HasMedia $model, string $collection, string $webPath, bool $dryRun): int
    {
        if ($model->hasMedia($collection)) {
            return 0;
        }

        $absolute = public_path(ltrim($webPath, '/'));

        if (! MediaUrl::isDisplayableFile($absolute)) {
            return 0;
        }

        $already = $model->getMedia($collection)
            ->contains(fn (Media $media): bool => $media->getCustomProperty('seed_source') === $webPath);

        if ($already) {
            return 0;
        }

        if ($dryRun) {
            $this->line(sprintf(
                '[dry-run] %s#%s → %s (%s)',
                $model::class,
                $model->getKey(),
                $collection,
                $webPath,
            ));

            return 1;
        }

        $media = $this->media->attachFromPath($model, $collection, $absolute);
        $media?->setCustomProperty('seed_source', $webPath)->save();

        return 1;
    }
}
