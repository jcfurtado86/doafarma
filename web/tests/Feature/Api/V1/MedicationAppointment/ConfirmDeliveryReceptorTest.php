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

it('should allow receptor to confirm delivery', function (): void {
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

    $response = patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor");

    $response->assertOk()
        ->assertJsonPath('data.receptor_confirmed', true)
        ->assertJsonPath('data.status', 'confirmed');
});

it('should complete appointment when both parties confirm', function (): void {
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
        'doctor_confirmed'      => true,
    ]);

    actingAs($receptor);

    $response = patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor");

    $response->assertOk()
        ->assertJsonPath('data.receptor_confirmed', true)
        ->assertJsonPath('data.doctor_confirmed', true)
        ->assertJsonPath('data.status', 'completed');

    // Verify offering is marked completed
    $offering->refresh();
    expect($offering->status)->toBe('completed');
});

it('should allow confirmation on scheduled date', function (): void {
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
        'scheduled_date'        => now()->toDateString(),
    ]);

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertOk();
});

// Authorization Error Tests

it('should return 401 for unauthenticated users', function (): void {
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'scheduled_date' => now()->subDays(1)->toDateString(),
    ]);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertUnauthorized();
});

it('should return 403 when doctor tries to confirm as receptor', function (): void {
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

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertForbidden();
});

it('should return 403 when receptor confirms another receptors appointment', function (): void {
    $receptor1 = User::factory()->receptor()->create();
    $receptor2 = User::factory()->receptor()->create();
    $doctor    = Doctor::factory()->create();
    $address   = Address::factory()->create(['user_id' => $doctor->user->id]);
    $offering  = MedicationOffering::factory()->reserved()->create(['doctor_id' => $doctor->id]);
    $request   = MedicationRequest::factory()
        ->forReceptor($receptor1)
        ->forOffering($offering)
        ->confirmed()
        ->create();
    $appointment = MedicationAppointment::factory()->confirmed()->create([
        'medication_request_id' => $request->id,
        'address_id'            => $address->id,
        'scheduled_date'        => now()->subDays(1)->toDateString(),
    ]);

    actingAs($receptor2);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertForbidden();
});

// Business Rule Error Tests

it('should return 422 when appointment is already completed', function (): void {
    $receptor = User::factory()->receptor()->create();
    $doctor   = Doctor::factory()->create();
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

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertStatus(422)
        ->assertJson(['message' => 'Este agendamento já foi concluído.']);
});

it('should return 422 when confirming before scheduled date', function (): void {
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
        'scheduled_date'        => now()->addDays(5)->toDateString(),
    ]);

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertStatus(422)
        ->assertJson(['message' => 'A entrega só pode ser confirmada a partir da data agendada.']);
});

it('should return 422 when receptor already confirmed', function (): void {
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
        'receptor_confirmed'    => true,
    ]);

    actingAs($receptor);

    patchJson("/api/v1/medication-appointments/{$appointment->id}/confirm-delivery-receptor")
        ->assertStatus(422)
        ->assertJson(['message' => 'Você já confirmou a entrega.']);
});

// Edge Cases

it('should return 404 for non-existent appointment', function (): void {
    $receptor = User::factory()->receptor()->create();

    actingAs($receptor);

    patchJson('/api/v1/medication-appointments/99999/confirm-delivery-receptor')
        ->assertNotFound();
});
