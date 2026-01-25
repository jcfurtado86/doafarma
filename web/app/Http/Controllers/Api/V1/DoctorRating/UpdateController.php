<?php

declare(strict_types = 1);

namespace App\Http\Controllers\Api\V1\DoctorRating;

use App\Actions\DoctorRating\UpdateRatingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DoctorRating\UpdateRatingRequest;
use App\Http\Resources\Api\V1\DoctorRatingResource;
use App\Models\DoctorRating;

class UpdateController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        DoctorRating $doctorRating,
        UpdateRatingRequest $request,
        UpdateRatingAction $action
    ): DoctorRatingResource {
        $rating = $action->execute($doctorRating, $request->validated());

        return DoctorRatingResource::make($rating);
    }
}
