<?php

declare(strict_types = 1);

namespace App\Actions\DoctorRating;

use App\Models\DoctorRating;
use Illuminate\Support\Facades\DB;

class UpdateRatingAction
{
    /**
     * Execute the action to update a doctor rating.
     *
     * @param array{rating?: int, comment?: string|null} $data
     */
    public function execute(DoctorRating $rating, array $data): DoctorRating
    {
        return DB::transaction(function () use ($rating, $data): DoctorRating {
            $updateData = [];

            if (isset($data['rating'])) {
                $updateData['rating'] = $data['rating'];
            }

            if (array_key_exists('comment', $data)) {
                $updateData['comment'] = $data['comment'];
            }

            if (! empty($updateData)) {
                $rating->update($updateData);
            }

            return $rating->fresh([
                'medicationAppointment.medicationRequest.medicationOffering.drug',
                'doctor.user',
                'receptor',
            ]);
        });
    }
}
