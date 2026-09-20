<?php

namespace App\Http\Controllers\Driver;

use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateDriverProfileRequest;
use App\Models\User;
use App\Services\Driver\DriverService;
use App\Support\DriverPayload;
use App\Support\Toast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ProfileController extends Controller
{
    public function __construct(private DriverService $drivers) {}

    /**
     * Field profile: identity, vehicle, branches, and roster for the signed-in driver.
     */
    public function index(Request $request): Response
    {
        $driver = $this->signedInDriver($request);

        return Inertia::render('driver/profile/index', [
            'driver' => DriverPayload::detail($driver),
        ]);
    }

    /**
     * Field editor for the signed-in driver's identity and vehicle details.
     */
    public function edit(Request $request): Response
    {
        $driver = $this->signedInDriver($request);

        return Inertia::render('driver/profile/edit', [
            'driver' => DriverPayload::detail($driver),
            'vehicleTypes' => DriverPayload::vehicleTypeOptions(),
            'mustVerifyPhone' => $driver->phone_verified_at === null,
        ]);
    }

    /**
     * Persist the signed-in driver's self-serve profile fields.
     */
    public function update(UpdateDriverProfileRequest $request): RedirectResponse
    {
        $driver = $this->signedInDriver($request);
        $this->ensureProfileExists($driver, $request->validated('vehicle_type'));

        $originalPhone = $driver->phone;
        $originalEmail = $driver->email;

        try {
            $this->drivers->update(
                $driver,
                $request->safe()->except(['avatar', 'vehicle_image']),
                avatar: $request->file('avatar'),
                vehicleImage: $request->file('vehicle_image'),
            );
        } catch (InvalidArgumentException $exception) {
            Toast::error($exception->getMessage());

            return redirect()->back();
        }

        $driver->refresh();

        if ($driver->phone !== $originalPhone) {
            $driver->forceFill(['phone_verified_at' => null])->save();
        }

        if ($driver->email !== $originalEmail) {
            $driver->forceFill(['email_verified_at' => null])->save();
        }

        Toast::success('Profile updated.');

        return redirect()->route('driver.profile.index');
    }

    private function signedInDriver(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return DriverPayload::resolveDriver($user);
    }

    private function ensureProfileExists(User $driver, ?string $vehicleType): void
    {
        if ($driver->driverProfile !== null) {
            return;
        }

        $driver->driverProfile()->create([
            'vehicle_type' => $vehicleType ?? DriverVehicleType::Bicycle->value,
            'availability_status' => DriverAvailabilityStatus::Offline->value,
        ]);

        $driver->unsetRelation('driverProfile');
    }
}
