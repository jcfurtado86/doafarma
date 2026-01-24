<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Jobs\SendAppointmentReminderJob;
use App\Models\MedicationAppointment;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendAppointmentRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for upcoming appointments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for appointments that need reminders...');

        $now           = Carbon::now();
        $in24Hours     = $now->copy()->addHours(24);
        $in1Hour       = $now->copy()->addHour();
        $remindersSent = 0;

        // Find appointments that need 24h reminder
        // Appointments scheduled for tomorrow at the same time (within 5 minute window)
        $appointments24h = MedicationAppointment::confirmed()
            ->whereDate('scheduled_date', $in24Hours->toDateString())
            ->whereRaw("scheduled_time BETWEEN ? AND ?", [
                $in24Hours->copy()->subMinutes(5)->format('H:i:s'),
                $in24Hours->copy()->addMinutes(5)->format('H:i:s'),
            ])
            ->get();

        foreach ($appointments24h as $appointment) {
            SendAppointmentReminderJob::dispatch($appointment->id, '24h');
            $remindersSent++;
            $this->line("Dispatched 24h reminder for appointment #{$appointment->id}");
        }

        // Find appointments that need 1h reminder
        $appointments1h = MedicationAppointment::confirmed()
            ->whereDate('scheduled_date', $in1Hour->toDateString())
            ->whereRaw("scheduled_time BETWEEN ? AND ?", [
                $in1Hour->copy()->subMinutes(5)->format('H:i:s'),
                $in1Hour->copy()->addMinutes(5)->format('H:i:s'),
            ])
            ->get();

        foreach ($appointments1h as $appointment) {
            SendAppointmentReminderJob::dispatch($appointment->id, '1h');
            $remindersSent++;
            $this->line("Dispatched 1h reminder for appointment #{$appointment->id}");
        }

        $this->info("Total reminders dispatched: {$remindersSent}");

        return self::SUCCESS;
    }
}
