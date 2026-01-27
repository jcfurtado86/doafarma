<?php

declare(strict_types = 1);

use App\Enums\UserStatus;
use App\Models\Doctor;
use App\Models\Drug;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

beforeEach(function (): void {
    Activity::query()->delete();
});

describe('User Activity Logging', function (): void {
    it('logs when a user is created', function (): void {
        $user = User::factory()->receptor()->create();

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'created')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->description)->toContain('foi criado');
    });

    it('logs when a user is updated', function (): void {
        $user = User::factory()->receptor()->create(['name' => 'Old Name']);
        Activity::query()->delete();

        $user->update(['name' => 'New Name']);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->properties['old']['name'])->toBe('Old Name')
            ->and($activity->properties['attributes']['name'])->toBe('New Name');
    });

    it('does not log when unchanged fields are saved', function (): void {
        $user = User::factory()->receptor()->create(['name' => 'Same Name']);
        Activity::query()->delete();

        $user->update(['name' => 'Same Name']);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->first();

        expect($activity)->toBeNull();
    });
});

describe('MedicationOffering Activity Logging', function (): void {
    it('logs when a medication offering is created', function (): void {
        $doctor = Doctor::factory()->create();
        $drug   = Drug::factory()->create();

        $offering = MedicationOffering::factory()->create([
            'doctor_id' => $doctor->id,
            'drug_id'   => $drug->id,
        ]);

        $activity = Activity::where('subject_type', MedicationOffering::class)
            ->where('subject_id', $offering->id)
            ->where('event', 'created')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->description)->toContain('criada');
    });

    it('logs when a medication offering status changes', function (): void {
        $offering = MedicationOffering::factory()->create(['status' => 'available']);
        Activity::query()->delete();

        $offering->update(['status' => 'reserved']);

        $activity = Activity::where('subject_type', MedicationOffering::class)
            ->where('subject_id', $offering->id)
            ->where('event', 'updated')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->properties['old']['status'])->toBe('available')
            ->and($activity->properties['attributes']['status'])->toBe('reserved');
    });
});

describe('MedicationRequest Activity Logging', function (): void {
    it('logs when a medication request is created', function (): void {
        $receptor = User::factory()->receptor()->create(['status' => UserStatus::Approved]);
        $offering = MedicationOffering::factory()->available()->create();
        Activity::query()->delete();

        $request = MedicationRequest::create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
            'status'                 => 'pending',
        ]);

        $activity = Activity::where('subject_type', MedicationRequest::class)
            ->where('subject_id', $request->id)
            ->where('event', 'created')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->description)->toContain('criada');
    });

    it('logs when a medication request status changes', function (): void {
        $request = MedicationRequest::factory()->pending()->create();
        Activity::query()->delete();

        $request->update(['status' => 'confirmed']);

        $activity = Activity::where('subject_type', MedicationRequest::class)
            ->where('subject_id', $request->id)
            ->where('event', 'updated')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->properties['old']['status'])->toBe('pending')
            ->and($activity->properties['attributes']['status'])->toBe('confirmed');
    });
});

describe('Activity Log Causer Tracking', function (): void {
    it('tracks the user who made the change', function (): void {
        $admin = User::factory()->admin()->create();
        auth()->login($admin);

        $user = User::factory()->receptor()->create();

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'created')
            ->first();

        expect($activity)->not->toBeNull()
            ->and($activity->causer_id)->toBe($admin->id)
            ->and($activity->causer_type)->toBe(User::class);

        auth()->logout();
    });
});
