<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\UserRole;
use Carbon\Carbon;
use Database\Factories\MedicationAppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property Carbon $scheduled_date
 */
class MedicationAppointment extends Model
{
    /** @use HasFactory<MedicationAppointmentFactory> */
    use HasFactory;
    use LogsActivity;

    /**
     * Full relationship set for API responses (mutations).
     *
     * @var array<int, string>
     */
    public const array RELATIONS_FULL = [
        'medicationRequest.medicationOffering.drug',
        'medicationRequest.medicationOffering.doctor.user',
        'medicationRequest.receptor',
        'address',
    ];

    /**
     * Relationships for doctor-facing views (excludes doctor.user since they're viewing their own data).
     *
     * @var array<int, string>
     */
    public const array RELATIONS_FOR_DOCTOR = [
        'medicationRequest.medicationOffering.drug',
        'medicationRequest.receptor',
        'address',
    ];

    /**
     * Relationships for receptor-facing views (excludes receptor since they're viewing their own data).
     *
     * @var array<int, string>
     */
    public const array RELATIONS_FOR_RECEPTOR = [
        'medicationRequest.medicationOffering.drug',
        'medicationRequest.medicationOffering.doctor.user',
        'address',
    ];

    #[\Override]
    protected $table = 'medication_appointments';

    #[\Override]
    protected $fillable = [
        'medication_request_id',
        'address_id',
        'scheduled_date',
        'scheduled_time',
        'status',
        'proposed_by',
        'receptor_confirmed',
        'doctor_confirmed',
    ];

    /**
     * The attributes that should be cast.
     */
    #[\Override]
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
     * Get the rating for this appointment.
     *
     * @return HasOne<DoctorRating, $this>
     */
    public function rating(): HasOne
    {
        return $this->hasOne(DoctorRating::class, 'medication_appointment_id');
    }

    /**
     * Scope a query to only include proposed appointments (awaiting acceptance).
     *
     * @param Builder<MedicationAppointment> $query
     * @return Builder<MedicationAppointment>
     */
    public function scopeProposed(Builder $query): Builder
    {
        return $query->where('status', 'proposed');
    }

    /**
     * Scope a query to only include confirmed appointments.
     *
     * @param Builder<MedicationAppointment> $query
     * @return Builder<MedicationAppointment>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
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
     * Check if the given user needs to respond to this appointment.
     * The user who did NOT propose is the one who needs to respond.
     */
    public function needsResponseFrom(User $user): bool
    {
        if ($this->status !== 'proposed') {
            return false;
        }

        $isReceptor = $user->role === UserRole::Receptor
            && $this->medicationRequest->receptor_id === $user->id;

        $isDoctor = $user->role === UserRole::Doctor
            && $user->doctor !== null
            && $this->medicationRequest->medicationOffering->doctor_id === $user->doctor->id;

        if ($this->proposed_by === 'receptor') {
            return $isDoctor;
        }

        return $isReceptor;
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

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['medication_request_id', 'address_id', 'scheduled_date', 'scheduled_time', 'status', 'receptor_confirmed', 'doctor_confirmed'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Agendamento #{$this->id} criado",
                'updated' => "Agendamento #{$this->id} atualizado",
                'deleted' => "Agendamento #{$this->id} removido",
                default   => "Agendamento #{$this->id}: {$eventName}",
            });
    }
}
