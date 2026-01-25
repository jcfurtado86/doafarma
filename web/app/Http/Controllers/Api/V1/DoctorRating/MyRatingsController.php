<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\DoctorRating;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DoctorRatingResource;
use App\Models\DoctorRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyRatingsController extends Controller
{
    /**
     * Get the authenticated doctor's ratings with summary statistics.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== UserRole::Doctor || ! $user->doctor) {
            return response()->json([
                'message' => 'Apenas médicos podem acessar suas avaliações.',
            ], 403);
        }

        $doctorId = $user->doctor->id;

        $ratings = DoctorRating::where('doctor_id', $doctorId)
            ->with(['receptor', 'medicationAppointment.medicationRequest.medicationOffering.drug'])
            ->orderByDesc('created_at')
            ->get();

        $totalRatings  = $ratings->count();
        $averageRating = $totalRatings > 0
            ? round($ratings->avg('rating'), 1)
            : 0;

        $ratingDistribution = [
            5 => $ratings->where('rating', 5)->count(),
            4 => $ratings->where('rating', 4)->count(),
            3 => $ratings->where('rating', 3)->count(),
            2 => $ratings->where('rating', 2)->count(),
            1 => $ratings->where('rating', 1)->count(),
        ];

        return response()->json([
            'summary' => [
                'total_ratings'       => $totalRatings,
                'average_rating'      => $averageRating,
                'rating_distribution' => $ratingDistribution,
            ],
            'ratings' => DoctorRatingResource::collection($ratings),
        ]);
    }
}
