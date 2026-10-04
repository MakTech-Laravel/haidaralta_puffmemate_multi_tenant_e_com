<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\UpdateBranchSettingsRequest;
use App\Models\Branch;
use App\Services\Branch\BranchService;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class GeneralSettingsController extends Controller
{
    public function __construct(private BranchService $branches) {}

    /**
     * Show the form for editing the current branch's general settings.
     */
    public function edit(): Response
    {
        $branch = $this->currentBranch();

        return Inertia::render('branch/settings/general', [
            'branch' => $this->mapBranchDetail($branch),
        ]);
    }

    /**
     * Update the current branch's general settings.
     */
    public function update(UpdateBranchSettingsRequest $request): RedirectResponse
    {
        $branch = $this->currentBranch();
        $validated = $request->validated();

        $this->branches->update(
            $branch,
            [
                ...$validated,
                'slug' => $branch->slug,
                'code' => $branch->code,
                'status' => $branch->status,
            ],
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        Toast::success('Branch settings updated.');

        return redirect()->route('branch.settings.general.edit', $branch);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapBranchDetail(Branch $branch): array
    {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
            'code' => $branch->code,
            'logo' => $branch->hasMedia('logo') ? 'logo' : null,
            'logo_url' => $this->branches->logoUrl($branch),
            'phone' => $branch->phone,
            'email' => $branch->email,
            'address' => $branch->address,
            'latitude' => $branch->latitude,
            'longitude' => $branch->longitude,
            'delivery_radius_km' => $branch->delivery_radius_km,
            'opening_time' => $branch->opening_time?->format('H:i'),
            'closing_time' => $branch->closing_time?->format('H:i'),
            'status' => $branch->status,
        ];
    }

    private function currentBranch(): Branch
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');

        return $branch;
    }
}
