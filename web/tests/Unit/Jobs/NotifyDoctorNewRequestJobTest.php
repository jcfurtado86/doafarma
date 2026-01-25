<?php

declare(strict_types = 1);

use App\Jobs\NotifyDoctorNewRequestJob;
use App\Models\MedicationRequest;
use App\Models\PushToken;
use Illuminate\Support\Facades\Http;

it('should not send notification for non-existent request', function (): void {
    Http::fake();

    $job = new NotifyDoctorNewRequestJob(999);
    $job->handle();

    Http::assertNothingSent();
});

it('should not send notification for non-pending request', function (): void {
    Http::fake();

    $request = MedicationRequest::factory()
        ->confirmed()
        ->create();

    $job = new NotifyDoctorNewRequestJob($request->id);
    $job->handle();

    Http::assertNothingSent();
});

it('should send notification to doctor when has token', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $request = MedicationRequest::factory()
        ->pending()
        ->create();

    // Create push token for doctor
    $doctorUserId = $request->medicationOffering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId]);

    $job = new NotifyDoctorNewRequestJob($request->id);
    $job->handle();

    Http::assertSentCount(1);
});

it('should not send notification if doctor has no push tokens', function (): void {
    Http::fake();

    $request = MedicationRequest::factory()
        ->pending()
        ->create();

    $job = new NotifyDoctorNewRequestJob($request->id);
    $job->handle();

    Http::assertNothingSent();
});

it('should include correct title and body in notification', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $request = MedicationRequest::factory()
        ->pending()
        ->create();

    $doctorUserId = $request->medicationOffering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId, 'token' => 'test-token']);

    $receptorName = $request->receptor->name;
    $drugName     = $request->medicationOffering->drug->product_name;

    $job = new NotifyDoctorNewRequestJob($request->id);
    $job->handle();

    Http::assertSent(function ($httpRequest) use ($receptorName, $drugName): bool {
        $body = $httpRequest->data();

        return isset($body[0]['title'])
            && $body[0]['title'] === 'Nova solicitação de medicamento'
            && isset($body[0]['body'])
            && str_contains((string) $body[0]['body'], (string) $receptorName)
            && str_contains((string) $body[0]['body'], (string) $drugName);
    });
});

it('should include correct data in notification payload', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $request = MedicationRequest::factory()
        ->pending()
        ->create();

    $doctorUserId = $request->medicationOffering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId]);

    $job = new NotifyDoctorNewRequestJob($request->id);
    $job->handle();

    Http::assertSent(function ($httpRequest) use ($request): bool {
        $body = $httpRequest->data();

        return isset($body[0]['data']['type'])
            && $body[0]['data']['type'] === 'new_medication_request'
            && isset($body[0]['data']['medication_request_id'])
            && $body[0]['data']['medication_request_id'] === $request->id;
    });
});

it('should handle failed push notification gracefully', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['error' => 'Server error'], 500),
    ]);

    $request = MedicationRequest::factory()
        ->pending()
        ->create();

    $doctorUserId = $request->medicationOffering->doctor->user_id;
    PushToken::factory()->create(['user_id' => $doctorUserId]);

    // Should not throw exception even when API fails
    $job = new NotifyDoctorNewRequestJob($request->id);
    $job->handle();

    expect(true)->toBeTrue();
});

it('should send to multiple tokens for same doctor', function (): void {
    Http::fake([
        'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
    ]);

    $request = MedicationRequest::factory()
        ->pending()
        ->create();

    $doctorUserId = $request->medicationOffering->doctor->user_id;

    // Create multiple tokens for the same doctor
    PushToken::factory()->count(3)->create(['user_id' => $doctorUserId]);

    $job = new NotifyDoctorNewRequestJob($request->id);
    $job->handle();

    // Should send to all 3 tokens in a single request
    Http::assertSent(function ($httpRequest): bool {
        $body = $httpRequest->data();

        return count($body) === 3;
    });
});
