<?php

declare(strict_types = 1);

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\MedicationAppointmentResource;
use App\Filament\Resources\MedicationOfferingResource;
use App\Filament\Resources\MedicationRequestResource;
use App\Models\Address;
use App\Models\Doctor;
use App\Models\Drug;
use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;

describe('Medication Offering Resource', function (): void {
    it('shows correct navigation badge for available offerings', function (): void {
        $doctor = Doctor::factory()->create();

        MedicationOffering::factory()->count(5)->create([
            'doctor_id' => $doctor->id,
            'status'    => 'available',
        ]);

        MedicationOffering::factory()->count(3)->create([
            'doctor_id' => $doctor->id,
            'status'    => 'completed',
        ]);

        expect(MedicationOfferingResource::getNavigationBadge())->toBe('5');
        expect(MedicationOfferingResource::getNavigationBadgeColor())->toBe('success');
    });

    it('shows zero badge when no available offerings', function (): void {
        expect(MedicationOfferingResource::getNavigationBadge())->toBe('0');
    });

    it('cannot create offerings from admin panel', function (): void {
        expect(MedicationOfferingResource::canCreate())->toBeFalse();
    });

    it('cannot edit offerings from admin panel', function (): void {
        $doctor   = Doctor::factory()->create();
        $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);

        expect(MedicationOfferingResource::canEdit($offering))->toBeFalse();
    });

    it('cannot delete offerings from admin panel', function (): void {
        $doctor   = Doctor::factory()->create();
        $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);

        expect(MedicationOfferingResource::canDelete($offering))->toBeFalse();
    });
});

describe('Medication Request Resource', function (): void {
    it('shows navigation badge for pending requests', function (): void {
        $receptor = User::factory()->create(['role' => UserRole::Receptor, 'status' => UserStatus::Approved]);
        $doctor   = Doctor::factory()->create();
        $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);

        MedicationRequest::factory()->count(3)->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
            'status'                 => 'pending',
        ]);

        expect(MedicationRequestResource::getNavigationBadge())->toBe('3');
        expect(MedicationRequestResource::getNavigationBadgeColor())->toBe('warning');
    });

    it('shows green badge when no pending requests', function (): void {
        expect(MedicationRequestResource::getNavigationBadge())->toBe('0');
        expect(MedicationRequestResource::getNavigationBadgeColor())->toBe('success');
    });

    it('cannot create requests from admin panel', function (): void {
        expect(MedicationRequestResource::canCreate())->toBeFalse();
    });

    it('cannot edit requests from admin panel', function (): void {
        $receptor = User::factory()->create(['role' => UserRole::Receptor, 'status' => UserStatus::Approved]);
        $doctor   = Doctor::factory()->create();
        $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);
        $request  = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
        ]);

        expect(MedicationRequestResource::canEdit($request))->toBeFalse();
    });
});

describe('Medication Appointment Resource', function (): void {
    it('shows navigation badge for upcoming confirmed appointments', function (): void {
        $receptor = User::factory()->create(['role' => UserRole::Receptor, 'status' => UserStatus::Approved]);
        $doctor   = Doctor::factory()->create();
        $address  = Address::factory()->create(['user_id' => $doctor->user_id]);
        $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);

        $request1 = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
        ]);

        $request2 = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => MedicationOffering::factory()->create(['doctor_id' => $doctor->id])->id,
        ]);

        MedicationAppointment::factory()->create([
            'medication_request_id' => $request1->id,
            'address_id'            => $address->id,
            'status'                => 'confirmed',
            'scheduled_date'        => now()->addDays(5),
        ]);

        MedicationAppointment::factory()->create([
            'medication_request_id' => $request2->id,
            'address_id'            => $address->id,
            'status'                => 'confirmed',
            'scheduled_date'        => now()->addDays(10),
        ]);

        expect(MedicationAppointmentResource::getNavigationBadge())->toBe('2');
        expect(MedicationAppointmentResource::getNavigationBadgeColor())->toBe('info');
    });

    it('shows zero badge when no upcoming appointments', function (): void {
        expect(MedicationAppointmentResource::getNavigationBadge())->toBe('0');
    });

    it('does not count past appointments in badge', function (): void {
        $receptor = User::factory()->create(['role' => UserRole::Receptor, 'status' => UserStatus::Approved]);
        $doctor   = Doctor::factory()->create();
        $address  = Address::factory()->create(['user_id' => $doctor->user_id]);
        $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);

        $request = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
        ]);

        MedicationAppointment::factory()->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
            'status'                => 'confirmed',
            'scheduled_date'        => now()->subDays(5),
        ]);

        expect(MedicationAppointmentResource::getNavigationBadge())->toBe('0');
    });

    it('cannot create appointments from admin panel', function (): void {
        expect(MedicationAppointmentResource::canCreate())->toBeFalse();
    });

    it('cannot edit appointments from admin panel', function (): void {
        $receptor = User::factory()->create(['role' => UserRole::Receptor, 'status' => UserStatus::Approved]);
        $doctor   = Doctor::factory()->create();
        $address  = Address::factory()->create(['user_id' => $doctor->user_id]);
        $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);
        $request  = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
        ]);
        $appointment = MedicationAppointment::factory()->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
        ]);

        expect(MedicationAppointmentResource::canEdit($appointment))->toBeFalse();
    });
});

describe('Reports Page Data Calculations', function (): void {
    it('calculates offerings correctly', function (): void {
        $doctor = Doctor::factory()->create();

        MedicationOffering::factory()->count(5)->create([
            'doctor_id' => $doctor->id,
            'status'    => 'available',
            'quantity'  => 10,
        ]);

        MedicationOffering::factory()->count(3)->create([
            'doctor_id' => $doctor->id,
            'status'    => 'completed',
            'quantity'  => 20,
        ]);

        expect(MedicationOffering::count())->toBe(8);
        expect(MedicationOffering::available()->count())->toBe(5);
        expect(MedicationOffering::completed()->count())->toBe(3);
        expect((int) MedicationOffering::sum('quantity'))->toBe(110);
    });

    it('calculates requests correctly', function (): void {
        $receptor = User::factory()->create(['role' => UserRole::Receptor, 'status' => UserStatus::Approved]);
        $doctor   = Doctor::factory()->create();
        $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);

        MedicationRequest::factory()->count(3)->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
            'status'                 => 'pending',
        ]);

        MedicationRequest::factory()->count(2)->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
            'status'                 => 'confirmed',
        ]);

        expect(MedicationRequest::pending()->count())->toBe(3);
        expect(MedicationRequest::confirmed()->count())->toBe(2);
    });

    it('calculates appointments correctly', function (): void {
        $receptor = User::factory()->create(['role' => UserRole::Receptor, 'status' => UserStatus::Approved]);
        $doctor   = Doctor::factory()->create();
        $address  = Address::factory()->create(['user_id' => $doctor->user_id]);
        $offering = MedicationOffering::factory()->create(['doctor_id' => $doctor->id]);
        $request  = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => $offering->id,
        ]);

        MedicationAppointment::factory()->create([
            'medication_request_id' => $request->id,
            'address_id'            => $address->id,
            'status'                => 'confirmed',
            'scheduled_date'        => now()->addDays(5),
        ]);

        $anotherRequest = MedicationRequest::factory()->create([
            'receptor_id'            => $receptor->id,
            'medication_offering_id' => MedicationOffering::factory()->create(['doctor_id' => $doctor->id])->id,
        ]);

        MedicationAppointment::factory()->create([
            'medication_request_id' => $anotherRequest->id,
            'address_id'            => $address->id,
            'status'                => 'completed',
        ]);

        expect(MedicationAppointment::confirmed()->count())->toBe(1);
        expect(MedicationAppointment::completed()->count())->toBe(1);
    });
});

describe('Top Doctors and Drugs Queries', function (): void {
    it('finds doctors with most offerings', function (): void {
        $doctor1 = Doctor::factory()->create();
        $doctor2 = Doctor::factory()->create();

        MedicationOffering::factory()->count(5)->create([
            'doctor_id' => $doctor1->id,
            'quantity'  => 10,
        ]);

        MedicationOffering::factory()->count(2)->create([
            'doctor_id' => $doctor2->id,
            'quantity'  => 20,
        ]);

        $topDoctors = Doctor::query()
            ->whereHas('medicationOfferings')
            ->withCount(['medicationOfferings as total_offerings'])
            ->withSum('medicationOfferings', 'quantity')
            ->orderByDesc('total_offerings')
            ->get();

        expect($topDoctors)->toHaveCount(2);
        expect($topDoctors->first()->id)->toBe($doctor1->id);
        expect($topDoctors->first()->total_offerings)->toBe(5);
    });

    it('finds drugs with most offerings', function (): void {
        $doctor = Doctor::factory()->create();
        $drug1  = Drug::factory()->create(['product_name' => 'Popular Drug']);
        $drug2  = Drug::factory()->create(['product_name' => 'Less Popular Drug']);

        MedicationOffering::factory()->count(10)->create([
            'doctor_id' => $doctor->id,
            'drug_id'   => $drug1->id,
            'quantity'  => 5,
        ]);

        MedicationOffering::factory()->count(3)->create([
            'doctor_id' => $doctor->id,
            'drug_id'   => $drug2->id,
            'quantity'  => 20,
        ]);

        $topDrugs = Drug::query()
            ->whereHas('medicationOfferings')
            ->withCount(['medicationOfferings as total_offerings'])
            ->withSum('medicationOfferings', 'quantity')
            ->orderByDesc('medication_offerings_sum_quantity')
            ->get();

        expect($topDrugs)->toHaveCount(2);
        // Drug2 has more total quantity (3*20=60 vs 10*5=50)
        expect($topDrugs->first()->id)->toBe($drug2->id);
    });
});
