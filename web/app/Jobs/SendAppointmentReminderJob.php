<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\MedicationAppointment;
use App\Models\PushToken;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminderJob implements ShouldQueue
{
    use Queueable;

    private const string EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    public function __construct(
        private readonly int $appointmentId,
        private readonly string $reminderType, // '24h' or '1h'
    ) {
        //
    }

    public function handle(): void
    {
        $appointment = MedicationAppointment::with([
            'medicationRequest.receptor',
            'medicationRequest.medicationOffering.doctor.user',
            'medicationRequest.medicationOffering.drug',
            'address',
        ])->find($this->appointmentId);

        if (! $appointment) {
            Log::warning('Appointment not found for reminder', [
                'appointment_id' => $this->appointmentId,
            ]);

            return;
        }

        // Only send reminders for confirmed appointments
        if ($appointment->status !== 'confirmed') {
            Log::info('Skipping reminder for non-confirmed appointment', [
                'appointment_id' => $this->appointmentId,
                'status'         => $appointment->status,
            ]);

            return;
        }

        $this->sendReminderToReceptor($appointment);
        $this->sendReminderToDoctor($appointment);
    }

    private function sendReminderToReceptor(MedicationAppointment $appointment): void
    {
        $receptor = $appointment->medicationRequest->receptor;

        if (! $receptor) {
            return;
        }

        $tokens = PushToken::where('user_id', $receptor->id)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info('No push tokens for receptor', ['user_id' => $receptor->id]);

            return;
        }

        $doctorName = $appointment->medicationRequest->medicationOffering->doctor->user->name;
        $drugName   = $appointment->medicationRequest->medicationOffering->drug->product_name;
        $appointment->scheduled_date->format('d/m/Y');
        $scheduledTime = $appointment->scheduled_time;
        $address       = $appointment->address->full_address;

        $title = $this->reminderType === '24h'
            ? 'Lembrete: Retirada de medicamento amanhã'
            : 'Lembrete: Retirada de medicamento em 1 hora';

        $body = sprintf(
            'Você tem agendamento para retirar %s com Dr(a). %s às %s em %s',
            $drugName,
            $doctorName,
            $scheduledTime,
            $address
        );

        $this->sendPushNotification($tokens, $title, $body, [
            'type'           => 'appointment_reminder',
            'appointment_id' => $appointment->id,
            'reminder_type'  => $this->reminderType,
        ]);
    }

    private function sendReminderToDoctor(MedicationAppointment $appointment): void
    {
        $doctor = $appointment->medicationRequest->medicationOffering->doctor;

        if (! $doctor || ! $doctor->user) {
            return;
        }

        $tokens = PushToken::where('user_id', $doctor->user->id)->pluck('token')->toArray();

        if (empty($tokens)) {
            Log::info('No push tokens for doctor', ['user_id' => $doctor->user->id]);

            return;
        }

        $receptorName = $appointment->medicationRequest->receptor->name;
        $drugName     = $appointment->medicationRequest->medicationOffering->drug->product_name;
        $appointment->scheduled_date->format('d/m/Y');
        $scheduledTime = $appointment->scheduled_time;
        $address       = $appointment->address->full_address;

        $title = $this->reminderType === '24h'
            ? 'Lembrete: Entrega de medicamento amanhã'
            : 'Lembrete: Entrega de medicamento em 1 hora';

        $body = sprintf(
            'Você tem agendamento para entregar %s para %s às %s em %s',
            $drugName,
            $receptorName,
            $scheduledTime,
            $address
        );

        $this->sendPushNotification($tokens, $title, $body, [
            'type'           => 'appointment_reminder',
            'appointment_id' => $appointment->id,
            'reminder_type'  => $this->reminderType,
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
                Log::info('Push notification sent successfully', [
                    'tokens_count' => count($tokens),
                    'title'        => $title,
                ]);
            } else {
                Log::error('Failed to send push notification', [
                    'status'   => $response->status(),
                    'response' => $response->json(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('Exception sending push notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
