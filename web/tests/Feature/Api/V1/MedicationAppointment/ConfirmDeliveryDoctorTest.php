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

it('should allow doctor to confirm delivery', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
        'scheduled_date'        => now()->subDays(1)->toDateString(),
    ]);

    actingAs($doctor->user);

    $response = patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor");

    $response->assertOk()
        ->assertJsonPath('data.doctor_confirmed', true)
        ->assertJsonPath('data.status', 'confirmed');
});

it('should complete appointment and offering when both confirm', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
        'scheduled_date'        => now()->subDays(1)->toDateString(),
        'receptor_confirmed'    => true,
    ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');

    $offering->refresh();
    expect($offering->status)->toBe('completed');
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'scheduled_date' => now()->subDays(1)->toDateString(),
    ]);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertUnauthorized();
});

it('should return 403 when receptor tries to confirm as doctor', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
        'scheduled_date'        => now()->subDays(1)->toDateString(),
    ]);

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertForbidden();
});

it('should return 403 when doctor confirms another doctors appointment', function (): void {
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
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
        'scheduled_date'        => now()->subDays(1)->toDateString(),
    ]);

    actingAs($doctorB->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertForbidden();
});

// Business Rule Error Tests

it('should return 422 when appointment is already completed', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->completed()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->completed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
    ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertStatus(422)
        ->assertJson(['message' => 'Este agendamento já foi concluído.']);
});

it('should return 422 when confirming before scheduled date', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
        'scheduled_date'        => now()->addDays(5)->toDateString(),
    ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertStatus(422)
        ->assertJson(['message' => 'A entrega só pode ser confirmada a partir da data agendada.']);
});

it('should return 422 when doctor already confirmed', function (): void {
    $doctor   = Doctor::factory()->create();
    $receptor = User::factory()->receptor()->create();
    $address  = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request  = MedicationRequest::factory()
        ->forReceptor($receptor)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
        'scheduled_date'        => now()->subDays(1)->toDateString(),
        'doctor_confirmed'      => true,
    ]);

    actingAs($doctor->user);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-doctor")
        ->assertStatus(422)
        ->assertJson(['message' => 'Você já confirmou a entrega.']);
});
