<?php

declare(strict_types = 1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon $scheduled_date
 */
class MedicationAppointment extends Model
{
    /** @use HasFactory<\Database\Factories\MedicationAppointmentFactory> */
    use HasFactory;

    protected $table = 'medication_appointments';

    protected $fillable = [
        'medication_request_id',
        'address_id',
        'scheduled_date',
        'scheduled_time',
        'status',
        'receptor_confirmed',
        'doctor_confirmed',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'scheduled_date'     => 'date:Y-m-d',
            'receptor_confirmed' => 'boolean',
            'doctor_confirmed'   => 'boolean',
        ];
    }

    /**
     * Get the medication request associated with this appointment.
     *
     * @return BelongsTo<MedicationRequest, $this>
     */
    public function medicationRequest(): BelongsTo
    {
        return $this->belongsTo(MedicationRequest::class);
    }

    /**
     * Get the address where the appointment will take place.
     *
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Scope a query to only include scheduled appointments.
     *
     * @param Builder<MedicationAppointment> $query
     * @return Builder<MedicationAppointment>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope a query to only include completed appointments.
     *
     * @param Builder<MedicationAppointment> $query
     * @return Builder<MedicationAppointment>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to order by upcoming appointments.
     *
     * @param Builder<MedicationAppointment> $query
     * @return Builder<MedicationAppointment>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->orderBy('scheduled_date')
            ->orderBy('scheduled_time');
    }
}
