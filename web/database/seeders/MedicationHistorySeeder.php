<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Drug;
use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MedicationHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates completed appointments to populate the receptor's medication history.
     * These represent medications that have already been delivered.
     */
    public function run(): void
    {
        $receptor = User::where('email', 'receptor@example.com')->first();
        $doctor   = User::where('email', 'doctor@example.com')->first();

        if (! $receptor) {
            $this->command->warn('Receptor user not found. Run ReceptorUserSeeder first.');

            return;
        }

        if (! $doctor || ! $doctor->doctor) {
            $this->command->warn('Doctor user not found. Run DoctorUserSeeder first.');

            return;
        }

        $doctorAddress = $doctor->addresses()->first();

        if (! $doctorAddress) {
            $this->command->warn('Doctor has no address. Update DoctorUserSeeder.');

            return;
        }

        // Get some drugs for the history
        $drugs = Drug::take(8)->get();

        if ($drugs->count() < 3) {
            $this->command->warn('Not enough drugs found. Run DrugSeeder first.');

            return;
        }

        // Create completed offerings and appointments (history)
        $historyItems = [
            [
                'drug_index'     => 5, // Different from regular offerings
                'lot_number'     => 'HIST2024A001',
                'quantity'       => 20,
                'days_ago'       => 30, // Received 30 days ago
                'expires_months' => 3,
            ],
            [
                'drug_index'     => 6,
                'lot_number'     => 'HIST2024B002',
                'quantity'       => 15,
                'days_ago'       => 14, // Received 14 days ago
                'expires_months' => 5,
            ],
            [
                'drug_index'     => 7,
                'lot_number'     => 'HIST2024C003',
                'quantity'       => 30,
                'days_ago'       => 7, // Received 7 days ago
                'expires_months' => 8,
            ],
        ];

        foreach ($historyItems as $item) {
            $drugIndex = $item['drug_index'];
            $drug      = $drugs[$drugIndex] ?? $drugs[0];

            // Create a completed offering
            $offering = MedicationOffering::create([
                'doctor_id'  => $doctor->doctor->id,
                'drug_id'    => $drug->id,
                'lot_number' => $item['lot_number'],
                'expires_at' => Carbon::now()->addMonths($item['expires_months']),
                'quantity'   => 0, // All delivered
                'status'     => 'completed',
            ]);

            // Create a confirmed request
            $request = MedicationRequest::create([
                'receptor_id'            => $receptor->id,
                'medication_offering_id' => $offering->id,
                'status'                 => 'confirmed',
            ]);

            // Create a completed appointment (this appears in history)
            $appointmentDate = Carbon::now()->subDays($item['days_ago']);
            MedicationAppointment::create([
                'medication_request_id' => $request->id,
                'address_id'            => $doctorAddress->id,
                'scheduled_date'        => $appointmentDate->format('Y-m-d'),
                'scheduled_time'        => '10:00:00',
                'status'                => 'completed',
                'proposed_by'           => 'receptor',
                'receptor_confirmed'    => true,
                'doctor_confirmed'      => true,
                'created_at'            => $appointmentDate->subDays(7),
                'updated_at'            => $appointmentDate, // Completion date
            ]);
        }

        $this->command->info('Created ' . count($historyItems) . ' completed appointments for medication history.');
    }
}
