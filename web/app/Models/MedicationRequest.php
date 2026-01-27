<?php

declare(strict_types = 1);

namespace App\Models;

use Database\Factories\MedicationRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MedicationRequest extends Model
{
    /** @use HasFactory<MedicationRequestFactory> */
    use HasFactory;
    use LogsActivity;

    protected $table = 'medication_requests';

    protected $fillable = [
        'receptor_id',
        'medication_offering_id',
        'status',
    ];

    /**
     * Get the receptor (user) who made the request.
     *
     * @return BelongsTo<User, $this>
     */
    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    /**
     * Get the medication offering that was requested.
     *
     * @return BelongsTo<MedicationOffering, $this>
     */
    public function medicationOffering(): BelongsTo
    {
        return $this->belongsTo(MedicationOffering::class);
    }

    /**
     * Get the appointment for this request.
     *
     * @return HasOne<MedicationAppointment, $this>
     */
    public function appointment(): HasOne
    {
        return $this->hasOne(MedicationAppointment::class);
    }

    /**
     * Scope a query to only include pending requests.
     *
     * @param Builder<MedicationRequest> $query
     * @return Builder<MedicationRequest>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include confirmed requests.
     *
     * @param Builder<MedicationRequest> $query
     * @return Builder<MedicationRequest>
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope a query to only include rejected requests.
     *
     * @param Builder<MedicationRequest> $query
     * @return Builder<MedicationRequest>
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['receptor_id', 'medication_offering_id', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Solicitação de medicamento #{$this->id} criada",
                'updated' => "Solicitação de medicamento #{$this->id} atualizada",
                'deleted' => "Solicitação de medicamento #{$this->id} removida",
                default   => "Solicitação #{$this->id}: {$eventName}",
            });
    }
}
