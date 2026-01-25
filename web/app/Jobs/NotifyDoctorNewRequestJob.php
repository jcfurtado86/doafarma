<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\MedicationRequest;
use App\Models\PushToken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyDoctorNewRequestJob implements ShouldQueue
{
    use Queueable;

    private const string EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    public function __construct(
        private readonly int $medicationRequestId,
    ) {
        //
    }

    public function handle(): void
    {
        $request = MedicationRequest::with([
            'receptor',
            'medicationOffering.doctor.user',
            'medicationOffering.drug',
        ])->find($this->medicationRequestId);

        if (! $request) {
            Log::warning('MedicationRequest not found for notification', [
                'medication_request_id' => $this->medicationRequestId,
            ]);

            return;
        }

        // Only notify for pending requests
        if ($request->status !== 'pending') {
            Log::info('Skipping notification for non-pending request', [
                'medication_request_id' => $this->medicationRequestId,
                'status'                => $request->status,
            ]);

            return;
        }

        $this->sendNotificationToDoctor($request);
    }

    private function sendNotificationToDoctor(MedicationRequest $request): void
    {
        $doctor = $request->medicationOffering->doctor;

        if (! $doctor || ! $doctor->user) {
            Log::warning('Doctor or doctor user not found for notification', [
                'medication_request_id' => $request->id,
            ]);

            return;
        }

        $tokens = PushToken::where('user_id', $doctor->user->id)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info('No push tokens for doctor', ['user_id' => $doctor->user->id]);

            return;
        }

        $receptorName = $request->receptor->name;
        $drugName     = $request->medicationOffering->drug->product_name;

        $title = 'Nova solicitação de medicamento';
        $body  = sprintf(
            '%s solicitou o medicamento %s',
            $receptorName,
            $drugName
        );

        $this->sendPushNotification($tokens, $title, $body, [
            'type'                  => 'new_medication_request',
            'medication_request_id' => $request->id,
        ]);
    }

    /**
     * @param array<string> $tokens
     * @param array<string, mixed> $data
     */
    private function sendPushNotification(array $tokens, string $title, string $body, array $data = []): void
    {
        $messages = [];

        foreach ($tokens as $token) {
            $messages[] = [
                'to'    => $token,
                'sound' => 'default',
                'title' => $title,
                'body'  => $body,
                'data'  => $data,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(self::EXPO_PUSH_URL, $messages);

            if ($response->successful()) {
                Log::info('Push notification sent successfully to doctor', [
                    'tokens_count' => count($tokens),
                    'title'        => $title,
                ]);
            } else {
                Log::error('Failed to send push notification to doctor', [
                    'status'   => $response->status(),
                    'response' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception sending push notification to doctor', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
