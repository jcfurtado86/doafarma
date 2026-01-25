<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorRating extends Model
{
    /** @use HasFactory<\Database\Factories\DoctorRatingFactory> */
    use HasFactory;

    protected $fillable = [
        'medication_appointment_id',
        'doctor_id',
        'receptor_id',
        'rating',
        'comment',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * Get the medication appointment associated with this rating.
     *
     * @return BelongsTo<MedicationAppointment, $this>
     */
    public function medicationAppointment(): BelongsTo
    {
        return $this->belongsTo(MedicationAppointment::class);
    }

    /**
     * Get the doctor being rated.
     *
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the receptor who gave this rating.
     *
     * @return BelongsTo<User, $this>
     */
    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }
}
