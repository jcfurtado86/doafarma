<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\MedicationOffering;
use App\Models\PushToken;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyDoctorExpiringMedicationJob implements ShouldQueue
{
    use Queueable;

    private const string EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    public function __construct(
        public readonly int $medicationOfferingId,
        public readonly int $daysUntilExpiration,
    ) {
        //
    }

    public function handle(): void
    {
        $offering = MedicationOffering::with([
            'doctor.user',
            'drug',
        ])->find($this->medicationOfferingId);

        if (! $offering) {
            Log::warning('MedicationOffering not found for expiration notification', [
                'medication_offering_id' => $this->medicationOfferingId,
            ]);

            return;
        }

        // Only notify for available offerings
        if ($offering->status !== 'available') {
            Log::info('Skipping expiration notification for non-available offering', [
                'medication_offering_id' => $this->medicationOfferingId,
                'status'                 => $offering->status,
            ]);

            return;
        }

        $this->sendNotificationToDoctor($offering);
    }

    private function sendNotificationToDoctor(MedicationOffering $offering): void
    {
        $doctor = $offering->doctor;

        if (! $doctor || ! $doctor->user) {
            Log::warning('Doctor or doctor user not found for expiration notification', [
                'medication_offering_id' => $offering->id,
            ]);

            return;
        }

        $tokens = PushToken::where('user_id', $doctor->user->id)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info('No push tokens for doctor (expiration notification)', [
                'user_id' => $doctor->user->id,
            ]);

            return;
        }

        $drugName  = $offering->drug->product_name;
        $expiresAt = $offering->expires_at->format('d/m/Y');
        $isUrgent  = $this->daysUntilExpiration <= 3;

        if ($isUrgent) {
            $title = 'Medicamento vencendo em breve';
            $body  = sprintf(
                'URGENTE: %s vence em %d %s (%s). Priorize a doação!',
                $drugName,
                $this->daysUntilExpiration,
                $this->daysUntilExpiration === 1 ? 'dia' : 'dias',
                $expiresAt
            );
        } else {
            $title = 'Medicamento próximo do vencimento';
            $body  = sprintf(
                '%s vence em %d dias (%s). Considere priorizar a doação.',
                $drugName,
                $this->daysUntilExpiration,
                $expiresAt
            );
        }

        $this->sendPushNotification($tokens, $title, $body, [
            'type'                   => 'expiring_medication',
            'medication_offering_id' => $offering->id,
            'days_until_expiration'  => $this->daysUntilExpiration,
            'is_urgent'              => $isUrgent,
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
                Log::info('Expiration notification sent successfully to doctor', [
                    'medication_offering_id' => $data['medication_offering_id'],
                    'tokens_count'           => count($tokens),
                    'days_until_expiration'  => $this->daysUntilExpiration,
                ]);
            } else {
                Log::error('Failed to send expiration notification to doctor', [
                    'medication_offering_id' => $data['medication_offering_id'],
                    'status'                 => $response->status(),
                    'response'               => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception sending expiration notification to doctor', [
                'medication_offering_id' => $data['medication_offering_id'],
                'error'                  => $e->getMessage(),
            ]);
        }
    }
}
