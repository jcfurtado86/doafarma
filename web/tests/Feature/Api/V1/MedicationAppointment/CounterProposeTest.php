<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\Doctor;
use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;

// Happy Path Tests

it('should allow doctor to counter-propose with new date and time', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
            'scheduled_date'        => Carbon::now()->addDays(5)->toDateString(),
            'scheduled_time'        => '10:00',
        ]);

    $newDate = Carbon::now()->addDays(7)->toDateString();

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => $newDate,
        'scheduled_time' => '14:00',
    ])
        ->assertOk()
        ->assertJsonPath('data.scheduled_date', $newDate)
        ->assertJsonPath('data.scheduled_time', '14:00')
        ->assertJsonPath('data.proposed_by', 'doctor')
        ->assertJsonPath('data.status', 'proposed');
});

it('should allow doctor to counter-propose with new address', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address1 = Address::factory()->create(['user_id' => $doctor->user->id]);
    $address2 = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address1->id,
        ]);

    $newDate = Carbon::now()->addDays(3)->toDateString();

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => $newDate,
        'scheduled_time' => '09:00',
        'address_id'     => $address2->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.address.id', $address2->id);
});

it('should allow receptor to counter-propose after doctor', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByDoctor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    $newDate = Carbon::now()->addDays(10)->toDateString();

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => $newDate,
        'scheduled_time' => '16:00',
    ])
        ->assertOk()
        ->assertJsonPath('data.scheduled_date', $newDate)
        ->assertJsonPath('data.scheduled_time', '16:00')
        ->assertJsonPath('data.proposed_by', 'receptor')
        ->assertJsonPath('data.status', 'proposed');
});

it('should preserve address if not sent in counter-proposal', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    $newDate = Carbon::now()->addDays(3)->toDateString();

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => $newDate,
        'scheduled_time' => '11:00',
    ])
        ->assertOk()
        ->assertJsonPath('data.address.id', $address->id);
});

it('should allow multiple counter-proposals in sequence', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    // Doctor counter-proposes
    actingAs($doctor->user);
    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
    ])->assertOk()->assertJsonPath('data.proposed_by', 'doctor');

    // Receptor counter-proposes
    actingAs($receptor);
    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(6)->toDateString(),
        'scheduled_time' => '11:00',
    ])->assertOk()->assertJsonPath('data.proposed_by', 'receptor');

    // Doctor counter-proposes again
    actingAs($doctor->user);
    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(7)->toDateString(),
        'scheduled_time' => '12:00',
    ])->assertOk()->assertJsonPath('data.proposed_by', 'doctor');
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    $appointment = MedicationAppointment::factory()->proposed()->create();

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
    ])->assertUnauthorized();
});

it('should return 403 when receptor tries to counter-propose own proposal', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
    ])->assertForbidden();
});

it('should return 403 when doctor tries to counter-propose own counter-proposal', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByDoctor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
    ])->assertForbidden();
});

it('should return 403 when doctor tries to counter-propose another doctors appointment', function (): void {
    $doctorA  = Doctor::factory()->create();
    $doctorB  = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctorA->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctorA->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctorB->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
    ])->assertForbidden();
});

// Validation Error Tests

it('should return 422 when scheduled_date is missing', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_time' => '10:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_date');
});

it('should return 422 when scheduled_time is missing', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_time');
});

it('should return 422 when scheduled_date is in the past', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->subDays(1)->toDateString(),
        'scheduled_time' => '10:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_date');
});

it('should return 422 when scheduled_time has invalid format', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => 'invalid-time',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scheduled_time');
});

it('should return 422 when address_id does not exist', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
        'address_id'     => 99999,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('address_id');
});

it('should return 422 when doctor uses another doctors address', function (): void {
    $doctorA  = Doctor::factory()->create();
    $doctorB  = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $addressA = Address::factory()->create(['user_id' => $doctorA->user->id]);
    $addressB = Address::factory()->create(['user_id' => $doctorB->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctorA->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $addressA->id,
        ]);

    actingAs($doctorA->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
        'address_id'     => $addressB->id,
    ])
        ->assertStatus(422)
        ->assertJson(['message' => 'O endereço selecionado não pertence a você.']);
});

// Business Rule Error Tests

it('should return 422 when trying to counter-propose confirmed appointment', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->confirmed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
    ])
        ->assertStatus(422)
        ->assertJson(['message' => 'Apenas agendamentos pendentes podem ser reagendados.']);
});

it('should return 422 when trying to counter-propose completed appointment', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->completed()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
    ])
        ->assertStatus(422);
});

it('should ignore address_id when receptor counter-proposes', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address1 = Address::factory()->create(['user_id' => $doctor->user->id]);
    $address2 = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByDoctor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address1->id,
        ]);

    actingAs($receptor);

    // Receptor tries to set address_id but it should be ignored
    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::now()->addDays(5)->toDateString(),
        'scheduled_time' => '10:00',
        'address_id'     => $address2->id,
    ])
        ->assertOk()
        ->assertJsonPath('data.address.id', $address1->id); // Should remain unchanged
});

// Boundary Cases

it('should accept counter-proposal with today date', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->proposedByReceptor()
        ->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/counter-propose", [
        'scheduled_date' => Carbon::today()->toDateString(),
        'scheduled_time' => '23:59',
    ])->assertOk();
});
