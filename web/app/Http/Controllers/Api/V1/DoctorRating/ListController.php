<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\DoctorRating;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DoctorRatingResource;
use App\Models\Doctor;
use App\Models\DoctorRating;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ListController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Doctor $doctor): AnonymousResourceCollection
    {
        $this->authorize('viewDoctorRatings', DoctorRating::class);

        $ratings = DoctorRating::where('doctor_id', $doctor->id)
            ->with(['receptor'])
            ->orderByDesc('created_at')
            ->get();

        return DoctorRatingResource::collection($ratings);
    }
}
