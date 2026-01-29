<?php

declare(strict_types = 1);

use App\Models\Address;
use App\Models\Doctor;
use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;

// Happy Path Tests

it('should allow doctor to accept receptor proposal', function (): void {
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

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.proposed_by', 'receptor');
});

it('should allow receptor to accept doctor counter-proposal', function (): void {
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

    actingAs($receptor, 'sanctum');

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.proposed_by', 'doctor');
});

it('should return appointment with all related data after accept', function (): void {
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

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'scheduled_date',
                'scheduled_time',
                'status',
                'proposed_by',
                'receptor_confirmed',
                'doctor_confirmed',
                'medication_request',
                'address',
            ],
        ]);
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    $appointment = MedicationAppointment::factory()->proposed()->create();

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertUnauthorized();
});

it('should return 403 when receptor tries to accept own proposal', function (): void {
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

    actingAs($receptor, 'sanctum');

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertForbidden();
});

it('should return 403 when doctor tries to accept own counter-proposal', function (): void {
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

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertForbidden();
});

it('should return 403 when doctor tries to accept another doctors appointment', function (): void {
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

    actingAs($doctorB->user, 'sanctum');

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertForbidden();
});

it('should return 403 when receptor tries to accept another receptors appointment', function (): void {
    $doctor    = Doctor::factory()->create();
    $receptorA = User::factory()->receptor()->create();
    $receptorB = User::factory()->receptor()->create();
    $address   = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering  = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request   = MedicationRequest::factory()
        ->forReceptor($receptorA)
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

    actingAs($receptorB, 'sanctum');

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertForbidden();
});

// Business Rule Error Tests

it('should return 422 when trying to accept already confirmed appointment', function (): void {
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

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertStatus(422)
        ->assertJson(['message' => 'Apenas agendamentos pendentes podem ser aceitos.']);
});

it('should return 422 when trying to accept completed appointment', function (): void {
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

    actingAs($doctor->user, 'sanctum');

    patchJson("/api/v1/medication-appointments/{$appointment->id}/accept")
        ->assertStatus(422);
});

it('should return 404 when trying to accept non-existent appointment', function (): void {
    $doctor = Doctor::factory()->create();

    actingAs($doctor->user, 'sanctum');

    patchJson('/api/v1/medication-appointments/99999/accept')
        ->assertNotFound();
});
