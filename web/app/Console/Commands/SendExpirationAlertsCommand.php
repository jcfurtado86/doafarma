<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Jobs\NotifyDoctorExpiringMedicationJob;
use App\Models\MedicationOffering;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendExpirationAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    #[\Override]
    protected $signature = 'medications:send-expiration-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    #[\Override]
    protected $description = 'Send notifications to doctors about medications near expiration date';

    /**
     * Alert thresholds in days.
     * Medications expiring within these days will trigger notifications.
     *
     * @var array<int>
     */
    private const array ALERT_THRESHOLDS = [7, 3, 1];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for medications near expiration...');

        $alertsSent = 0;
        $today      = Carbon::today();

        foreach (self::ALERT_THRESHOLDS as $daysThreshold) {
            $expirationDate = $today->copy()->addDays($daysThreshold);

            // Find available offerings that expire on this exact date
            $offerings = MedicationOffering::available()
                ->whereDate('expires_at', $expirationDate)
                ->where('quantity', '>', 0)
                ->get();

            foreach ($offerings as $offering) {
                NotifyDoctorExpiringMedicationJob::dispatch(
                    $offering->id,
                    $daysThreshold
                );

                $alertsSent++;
                $this->line(sprintf(
                    'Dispatched %d-day expiration alert for offering #%d (%s)',
                    $daysThreshold,
                    $offering->id,
                    $offering->drug->product_name ?? 'Unknown drug'
                ));
            }
        }

        $this->info("Total expiration alerts dispatched: {$alertsSent}");

        return self::SUCCESS;
    }
}
