<?php

declare(strict_types = 1);

use App\Jobs\SendAppointmentReminderJob;
use App\Models\MedicationAppointment;
use App\Models\PushToken;
use Illuminate\Support\Facades\Http;

it('should not send reminder for non-existent appointment', function (): void {
    Http::fake();

    $job = new SendAppointmentReminderJob(999, '24h');
    $job->handle();

    Http::assertNothingSent();
});

it('should not send reminder for non-confirmed appointment', function (): void {
    Http::fake();

    $appointment = MedicationAppointment::factory()
        ->proposed()
        ->create();

    $job = new SendAppointmentReminderJob($appointment->id, '24h');
    $job->handle();

    Http::assertNothingSent();
});

it('should send reminder to receptor and doctor when both have tokens', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $appointment = MedicationAppointment::factory()
        ->confirmed()
        ->create([
            'scheduled_date' => now()->addDay()->toDateString(),
            'scheduled_time' => '10:00',
        ]);

    // Create push tokens for receptor and doctor
    $receptorId = $appointment->medicationRequest->receptor_id;
    $doctorId   = $appointment->medicationRequest->medicationOffering->doctor->user_id;

    PushToken::factory()->create(['user_id' => $receptorId]);
    PushToken::factory()->create(['user_id' => $doctorId]);

    $job = new SendAppointmentReminderJob($appointment->id, '24h');
    $job->handle();

    // Should make 2 API calls (one for receptor, one for doctor)
    Http::assertSentCount(2);
});

it('should not send reminder if no push tokens exist', function (): void {
    Http::fake();

    $appointment = MedicationAppointment::factory()
        ->confirmed()
        ->create();

    $job = new SendAppointmentReminderJob($appointment->id, '1h');
    $job->handle();

    Http::assertNothingSent();
});

it('should include correct data in 24h reminder', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $appointment = MedicationAppointment::factory()
        ->confirmed()
        ->create();

    $receptorId = $appointment->medicationRequest->receptor_id;
    PushToken::factory()->create(['user_id' => $receptorId, 'token' => 'test-token']);

    $job = new SendAppointmentReminderJob($appointment->id, '24h');
    $job->handle();

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return isset($body[0]['title'])
            && str_contains((string) $body[0]['title'], 'amanhã')
            && isset($body[0]['data']['reminder_type'])
            && $body[0]['data']['reminder_type'] === '24h';
    });
});

it('should include correct data in 1h reminder', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $appointment = MedicationAppointment::factory()
        ->confirmed()
        ->create();

    $receptorId = $appointment->medicationRequest->receptor_id;
    PushToken::factory()->create(['user_id' => $receptorId, 'token' => 'test-token-1h']);

    $job = new SendAppointmentReminderJob($appointment->id, '1h');
    $job->handle();

    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return isset($body[0]['title'])
            && str_contains((string) $body[0]['title'], '1 hora')
            && isset($body[0]['data']['reminder_type'])
            && $body[0]['data']['reminder_type'] === '1h';
    });
});

it('should handle failed push notification gracefully', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['error' => 'Server error'], 500),
    ]);

    $appointment = MedicationAppointment::factory()
        ->confirmed()
        ->create();

    $receptorId = $appointment->medicationRequest->receptor_id;
    PushToken::factory()->create(['user_id' => $receptorId]);

    // Should not throw exception even when API fails
    $job = new SendAppointmentReminderJob($appointment->id, '24h');
    $job->handle();

    expect(true)->toBeTrue();
});

it('should send to multiple tokens for same user', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $appointment = MedicationAppointment::factory()
        ->confirmed()
        ->create();

    $receptorId = $appointment->medicationRequest->receptor_id;

    // Create multiple tokens for the same user
    PushToken::factory()->count(3)->create(['user_id' => $receptorId]);

    $job = new SendAppointmentReminderJob($appointment->id, '24h');
    $job->handle();

    // Should send to all 3 tokens in a single request
    Http::assertSent(function ($request): bool {
        $body = $request->data();

        return count($body) === 3;
    });
});
