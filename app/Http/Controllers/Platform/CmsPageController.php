<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdateCmsPageRequest;
use App\Services\Cms\CmsPageService;
use App\Support\Cms\CmsPageDefaults;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CmsPageController extends Controller
{
    public function __construct(
        private readonly CmsPageService $pages,
    ) {}

    public function index(): Response
    {
        $pages = $this->pages->all()->map(fn ($page) => [
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->title,
            'is_published' => $page->is_published,
            'updated_at' => $page->updated_at?->toIso8601String(),
        ]);

        return Inertia::render('platform/pages/index', [
            'pages' => $pages,
        ]);
    }

    public function edit(string $slug): Response
    {
        if (! in_array($slug, CmsPageDefaults::slugs(), true)) {
            abort(404);
        }

        $page = $this->pages->findBySlug($slug);

        if ($page === null) {
            abort(404);
        }

        return Inertia::render('platform/pages/edit', [
            'page' => $this->pages->formPayload($page),
        ]);
    }

    public function update(UpdateCmsPageRequest $request, string $slug): RedirectResponse
    {
        if (! in_array($slug, CmsPageDefaults::slugs(), true)) {
            abort(404);
        }

        $validated = $request->validated();

        $this->pages->update(
            $slug,
            $validated,
            $request->user()?->id,
            $request->file('hero_image'),
            $request->file('media_image'),
        );

        Toast::success('Page saved.');

        return redirect()->route('platform.pages.edit', $slug);
    }
}
