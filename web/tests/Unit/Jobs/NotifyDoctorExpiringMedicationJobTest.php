<?php

declare(strict_types = 1);

use App\Jobs\NotifyDoctorExpiringMedicationJob;
use App\Models\MedicationOffering;
use App\Models\PushToken;
use Illuminate\Support\Facades\Http;

it('should not send notification for non-existent offering', function (): void {
    Http::fake();

    $job = new NotifyDoctorExpiringMedicationJob(999, 7);
    $job->handle();

    Http::assertNothingSent();
});

it('should not send notification for reserved offering', function (): void {
    Http::fake();

    $offering = MedicationOffering::factory()
        ->reserved()
        ->expiresInDays(7)
        ->create();

    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 7);
    $job->handle();

    Http::assertNothingSent();
});

it('should not send notification for completed offering', function (): void {
    Http::fake();

    $offering = MedicationOffering::factory()
        ->completed()
        ->expiresInDays(7)
        ->create();

    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 7);
    $job->handle();

    Http::assertNothingSent();
});

it('should send notification to doctor when has token for 7 days threshold', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(7)
        ->create();

    $doctorUserId = $offering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId]);

    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 7);
    $job->handle();

    Http::assertSentCount(1);
});

it('should send urgent notification for 3 days threshold', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(3)
        ->create();

    $doctorUserId = $offering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId, 'token' => 'test-token']);

    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 3);
    $job->handle();

    Http::assertSent(function ($httpRequest): bool {
        $body = $httpRequest->data();

        return isset($body[0]['title'])
            && $body[0]['title'] === 'Medicamento vencendo em breve'
            && isset($body[0]['body'])
            && str_contains((string) $body[0]['body'], 'URGENTE');
    });
});

it('should send urgent notification for 1 day threshold', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(1)
        ->create();

    $doctorUserId = $offering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId, 'token' => 'test-token']);

    $drugName = $offering->drug->product_name;

    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 1);
    $job->handle();

    Http::assertSent(function ($httpRequest) use ($drugName): bool {
        $body = $httpRequest->data();

        return isset($body[0]['body'])
            && str_contains((string) $body[0]['body'], 'URGENTE')
            && str_contains((string) $body[0]['body'], (string) $drugName)
            && str_contains((string) $body[0]['body'], '1 dia');
    });
});

it('should not send notification if doctor has no push tokens', function (): void {
    Http::fake();

    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(7)
        ->create();

    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 7);
    $job->handle();

    Http::assertNothingSent();
});

it('should include correct title and body for non-urgent notification', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(7)
        ->create();

    $doctorUserId = $offering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId, 'token' => 'test-token']);

    $drugName = $offering->drug->product_name;

    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 7);
    $job->handle();

    Http::assertSent(function ($httpRequest) use ($drugName): bool {
        $body = $httpRequest->data();

        return isset($body[0]['title'])
            && $body[0]['title'] === 'Medicamento próximo do vencimento'
            && isset($body[0]['body'])
            && str_contains((string) $body[0]['body'], (string) $drugName)
            && str_contains((string) $body[0]['body'], '7 dias');
    });
});

it('should include correct data in notification payload', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(3)
        ->create();

    $doctorUserId = $offering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId]);

    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 3);
    $job->handle();

    Http::assertSent(function ($httpRequest) use ($offering): bool {
        $body = $httpRequest->data();

        return isset($body[0]['data']['type'])
            && $body[0]['data']['type'] === 'expiring_medication'
            && isset($body[0]['data']['medication_offering_id'])
            && $body[0]['data']['medication_offering_id'] === $offering->id
            && isset($body[0]['data']['days_until_expiration'])
            && $body[0]['data']['days_until_expiration'] === 3
            && isset($body[0]['data']['is_urgent'])
            && $body[0]['data']['is_urgent'] === true;
    });
});

it('should handle failed push notification gracefully', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['error' => 'Server error'], 500),
    ]);

    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(7)
        ->create();

    $doctorUserId = $offering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId]);

    // Should not throw exception even when API fails
    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 7);
    $job->handle();

    expect(true)->toBeTrue();
});

it('should send to multiple tokens for same doctor', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $offering = MedicationOffering::factory()
        ->available()
        ->expiresInDays(7)
        ->create();

    $doctorUserId = $offering->doctor->user_id;

    // Create multiple tokens for the same doctor
    PushToken::factory()->count(3)->create(['user_id' => $doctorUserId]);

    $job = new NotifyDoctorExpiringMedicationJob($offering->id, 7);
    $job->handle();

    // Should send to all 3 tokens in a single request
    Http::assertSent(function ($httpRequest): bool {
        $body = $httpRequest->data();

        return count($body) === 3;
    });
});
