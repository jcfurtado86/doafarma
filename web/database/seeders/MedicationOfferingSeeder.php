<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\Drug;
use App\Models\MedicationOffering;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MedicationOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctor = User::where('email', 'doctor@example.com')->first();

        if (! $doctor || ! $doctor->doctor) {
            $this->command->warn('Doctor user not found. Run DoctorUserSeeder first.');

            return;
        }

        $doctorId = $doctor->doctor->id;

        // Get some drugs to create offerings
        $drugs = Drug::take(5)->get();

        if ($drugs->isEmpty()) {
            $this->command->warn('No drugs found. Run DrugSeeder first.');

            return;
        }

        $offerings = [
            [
                'doctor_id'  => $doctorId,
                'drug_id'    => $drugs[0]->id,
                'lot_number' => 'LOT2025A001',
                'expires_at' => Carbon::now()->addMonths(6),
                'quantity'   => 30,
                'status'     => 'available',
            ],
            [
                'doctor_id'  => $doctorId,
                'drug_id'    => $drugs[1]->id,
                'lot_number' => 'LOT2025B002',
                'expires_at' => Carbon::now()->addMonths(8),
                'quantity'   => 60,
                'status'     => 'available',
            ],
            [
                'doctor_id'  => $doctorId,
                'drug_id'    => $drugs[2]->id,
                'lot_number' => 'LOT2025C003',
                'expires_at' => Carbon::now()->addMonths(4),
                'quantity'   => 28,
                'status'     => 'available',
            ],
            [
                'doctor_id'  => $doctorId,
                'drug_id'    => $drugs[3]->id,
                'lot_number' => 'LOT2025D004',
                'expires_at' => Carbon::now()->addMonths(10),
                'quantity'   => 30,
                'status'     => 'available',
            ],
            [
                'doctor_id'  => $doctorId,
                'drug_id'    => $drugs[4]->id ?? $drugs[0]->id,
                'lot_number' => 'LOT2025E005',
                'expires_at' => Carbon::now()->addMonths(12),
                'quantity'   => 30,
                'status'     => 'available',
            ],
        ];

        foreach ($offerings as $offering) {
            MedicationOffering::create($offering);
        }
    }
}
