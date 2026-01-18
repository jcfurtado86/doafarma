<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MedicationRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
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

        // Get available offerings
        $offerings = MedicationOffering::where('status', 'available')->get();

        if ($offerings->isEmpty()) {
            $this->command->warn('No offerings found. Run MedicationOfferingSeeder first.');

            return;
        }

        // Get doctor's first address for the appointment
        $doctorAddress = $doctor->addresses()->first();

        if (! $doctorAddress) {
            $this->command->warn('Doctor has no address. Update DoctorUserSeeder.');

            return;
        }

        // Create a confirmed request with a proposed appointment (negotiation in progress)
        $offering1 = $offerings->first();
        $offering1->update(['status' => 'reserved']);

        $request1 = MedicationRequest::create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering1->id,
            'status'                 => 'confirmed',
        ]);

        MedicationAppointment::create([
            'medication_request_id' => $request1->id,
            'address_id'            => $doctorAddress->id,
            'scheduled_date'        => Carbon::now()->addDays(3)->format('Y-m-d'),
            'scheduled_time'        => '14:00:00',
            'status'                => 'proposed',
            'proposed_by'           => 'receptor',
            'receptor_confirmed'    => false,
            'doctor_confirmed'      => false,
        ]);

        // Create another confirmed request with a confirmed appointment (ready for delivery)
        if ($offerings->count() >= 2) {
            $offering2 = $offerings->skip(1)->first();
            $offering2->update(['status' => 'reserved']);

            $request2 = MedicationRequest::create([
                'receptor_id'            => $receptor->id,
                'medication_offering_id' => $offering2->id,
                'status'                 => 'confirmed',
            ]);

            MedicationAppointment::create([
                'medication_request_id' => $request2->id,
                'address_id'            => $doctorAddress->id,
                'scheduled_date'        => Carbon::now()->addDays(1)->format('Y-m-d'),
                'scheduled_time'        => '10:00:00',
                'status'                => 'confirmed',
                'proposed_by'           => 'doctor',
                'receptor_confirmed'    => false,
                'doctor_confirmed'      => false,
            ]);
        }

        // Create a pending request (waiting for doctor approval)
        if ($offerings->count() >= 3) {
            $offering3 = $offerings->skip(2)->first();

            MedicationRequest::create([
                'receptor_id'            => $receptor->id,
                'medication_offering_id' => $offering3->id,
                'status'                 => 'pending',
            ]);
        }
    }
}
