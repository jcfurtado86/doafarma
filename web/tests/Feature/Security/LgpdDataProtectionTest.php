<?php

declare(strict_types = 1);

use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| LGPD Data Protection Tests
|--------------------------------------------------------------------------
|
| Tests to verify that sensitive personal data is properly protected
| according to LGPD (Lei Geral de Proteção de Dados) requirements.
|
*/

describe('CPF Encryption', function (): void {
    it('should store CPF encrypted in database', function (): void {
        $user = User::factory()->receptor()->create([
            'cpf' => '52998224725',
        ]);

        // Get raw value from database
        $rawCpf = DB::table('users')->where('id', $user->id)->value('cpf');

        // CPF should be encrypted (not the plain value)
        expect($rawCpf)->not->toBe('52998224725');
        expect($rawCpf)->toStartWith('eyJ'); // Laravel encryption starts with base64-encoded JSON
    });

    it('should decrypt CPF correctly when accessed via model', function (): void {
        $user = User::factory()->receptor()->create([
            'cpf' => '52998224725',
        ]);

        // Fresh load from database
        $loadedUser = User::find($user->id);

        // Model should decrypt automatically
        expect($loadedUser->cpf)->toBe('52998224725');
    });

    it('should generate cpf_hash for lookups', function (): void {
        $user = User::factory()->receptor()->create([
            'cpf' => '52998224725',
        ]);

        // Check that hash was generated
        expect($user->cpf_hash)->toBe(hash('sha256', '52998224725'));
    });

    it('should allow finding user by cpf_hash', function (): void {
        $user = User::factory()->receptor()->create([
            'cpf' => '52998224725',
        ]);

        $found = User::where('cpf_hash', hash('sha256', '52998224725'))->first();

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($user->id);
    });
});

describe('Activity Log Privacy', function (): void {
    it('should NOT log CPF in activity log', function (): void {
        $user = User::factory()->receptor()->create([
            'cpf' => '52998224725',
        ]);

        $user->update(['cpf' => '14538220620']);

        // Check activity log
        $activity = DB::table('activity_log')
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->latest()
            ->first();

        // If there's an activity log, CPF should not be in properties
        if ($activity && $activity->properties) {
            $properties = json_decode((string) $activity->properties, true);
            expect($properties)->not->toHaveKey('old.cpf');
            expect($properties)->not->toHaveKey('attributes.cpf');

            if (isset($properties['old'])) {
                expect($properties['old'])->not->toHaveKey('cpf');
            }

            if (isset($properties['attributes'])) {
                expect($properties['attributes'])->not->toHaveKey('cpf');
            }
        }
    });

    it('should NOT log phone_number in activity log', function (): void {
        $user = User::factory()->receptor()->create([
            'phone_number' => '11987654321',
        ]);

        $user->update(['phone_number' => '21987654321']);

        // Check activity log
        $activity = DB::table('activity_log')
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->latest()
            ->first();

        // If there's an activity log, phone_number should not be in properties
        if ($activity && $activity->properties) {
            $properties = json_decode((string) $activity->properties, true);

            if (isset($properties['old'])) {
                expect($properties['old'])->not->toHaveKey('phone_number');
            }

            if (isset($properties['attributes'])) {
                expect($properties['attributes'])->not->toHaveKey('phone_number');
            }
        }
    });
});

describe('API Data Masking', function (): void {
    it('should mask receptor email in pending medication requests', function (): void {
        $receptor = User::factory()->receptor()->create([
            'email' => 'johndoe@example.com',
        ]);

        $doctor = User::factory()->doctor()->create();

        $offering = MedicationOffering::factory()->create([
            'doctor_id' => $doctor->doctor->id,
            'status'    => 'available',
        ]);

        $request = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
            'status'                 => 'pending',
        ]);

        $response = actingAs($doctor, 'sanctum')
            ->getJson(route('api.v1.medication-requests.received'));

        $response->assertOk();

        $data = $response->json('data.0.receptor');

        // Email should be masked
        expect($data['email'])->not->toBe('johndoe@example.com');
        expect($data['email'])->toContain('*');
    });

    it('should mask receptor phone in pending medication requests', function (): void {
        $receptor = User::factory()->receptor()->create([
            'phone_number' => '11987654321',
        ]);

        $doctor = User::factory()->doctor()->create();

        $offering = MedicationOffering::factory()->create([
            'doctor_id' => $doctor->doctor->id,
            'status'    => 'available',
        ]);

        $request = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
            'status'                 => 'pending',
        ]);

        $response = actingAs($doctor, 'sanctum')
            ->getJson(route('api.v1.medication-requests.received'));

        $response->assertOk();

        $data = $response->json('data.0.receptor');

        // Phone should be masked
        expect($data['phone_number'])->not->toBe('11987654321');
        expect($data['phone_number'])->toContain('*');
    });

    it('should expose full receptor contact info in confirmed medication requests', function (): void {
        $receptor = User::factory()->receptor()->create([
            'email'        => 'johndoe@example.com',
            'phone_number' => '11987654321',
        ]);

        $doctor = User::factory()->doctor()->create();

        $offering = MedicationOffering::factory()->create([
            'doctor_id' => $doctor->doctor->id,
            'status'    => 'reserved',
        ]);

        $request = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
            'status'                 => 'confirmed',
        ]);

        $response = actingAs($doctor, 'sanctum')
            ->getJson(route('api.v1.medication-requests.received'));

        $response->assertOk();

        $data = $response->json('data.0.receptor');

        // Full contact info should be exposed for confirmed requests
        expect($data['email'])->toBe('johndoe@example.com');
        expect($data['phone_number'])->toBe('11987654321');
    });
});

describe('CPF Unique Validation with Encryption', function (): void {
    it('should prevent duplicate CPF registration', function (): void {
        User::factory()->receptor()->create([
            'cpf' => '52998224725',
        ]);

        // Try to create another user with same CPF
        $response = \Pest\Laravel\postJson(route('receptor.register'), [
            'name'                  => 'Duplicate User',
            'email'                 => 'duplicate@example.com',
            'cpf'                   => '529.982.247-25', // Same CPF with mask
            'phone_number'          => '(21) 98765-4321',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'device_name'           => 'Test Device',
            'terms_accepted'        => true,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['cpf']);
    });
});
