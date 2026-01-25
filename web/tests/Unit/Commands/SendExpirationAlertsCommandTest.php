<?php

declare(strict_types = 1);

use App\Jobs\NotifyDoctorExpiringMedicationJob;
use App\Models\MedicationOffering;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Queue::fake();
});

it('dispatches job for offerings expiring in 7 days', function (): void {
    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(7)
        ->create();

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertPushed(NotifyDoctorExpiringMedicationJob::class, fn ($job): bool => $job->medicationOfferingId === $offering->id
        && $job->daysUntilExpiration === 7);
});

it('dispatches job for offerings expiring in 3 days', function (): void {
    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(3)
        ->create();

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertPushed(NotifyDoctorExpiringMedicationJob::class, fn ($job): bool => $job->medicationOfferingId === $offering->id
        && $job->daysUntilExpiration === 3);
});

it('dispatches job for offerings expiring in 1 day', function (): void {
    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(1)
        ->create();

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertPushed(NotifyDoctorExpiringMedicationJob::class, fn ($job): bool => $job->medicationOfferingId === $offering->id
        && $job->daysUntilExpiration === 1);
});

it('dispatches multiple jobs for offerings at different thresholds', function (): void {
    $offering7Days = MedicationOffering::factory()
        ->available()
        ->expiresInDays(7)
        ->create();

    $offering3Days = MedicationOffering::factory()
        ->available()
        ->expiresInDays(3)
        ->create();

    $offering1Day = MedicationOffering::factory()
        ->available()
        ->expiresInDays(1)
        ->create();

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertPushed(NotifyDoctorExpiringMedicationJob::class, 3);
});

it('does not dispatch job for reserved offerings', function (): void {
    MedicationOffering::factory()
        ->reserved()
        ->expiresInDays(7)
        ->create();

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('does not dispatch job for completed offerings', function (): void {
    MedicationOffering::factory()
        ->completed()
        ->expiresInDays(7)
        ->create();

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('does not dispatch job for offerings with zero quantity', function (): void {
    MedicationOffering::factory()
        ->available()
        ->expiresInDays(7)
        ->create(['quantity' => 0]);

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('does not dispatch job for offerings expiring in 5 days', function (): void {
    MedicationOffering::factory()
        ->available()
        ->expiresInDays(5)
        ->create();

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('does not dispatch job for offerings expiring in 30 days', function (): void {
    MedicationOffering::factory()
        ->available()
        ->expiresInDays(30)
        ->create();

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('does not dispatch job for already expired offerings', function (): void {
    MedicationOffering::factory()
        ->available()
        ->create(['expires_at' => now()->subDay()->toDateString()]);

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

it('dispatches jobs for multiple offerings expiring on same day', function (): void {
    MedicationOffering::factory()
        ->available()
        ->expiresInDays(7)
        ->count(3)
        ->create();

    $this->artisan('medications:send-expiration-alerts')
        ->assertSuccessful();

    Queue::assertPushed(NotifyDoctorExpiringMedicationJob::class, 3);
});
