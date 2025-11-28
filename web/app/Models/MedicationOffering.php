<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationOffering extends Model
{
    /** @use HasFactory<\Database\Factories\MedicationOfferingFactory> */
    use HasFactory;

    protected $table = 'medication_offerings';

    protected $fillable = [
        'doctor_id',
        'drug_id',
        'lot_number',
        'expires_at',
        'quantity',
    ];

    /**
     * The attributes that should be cast.
     */
    public function casts(): array
    {
        return [
            'expires_at' => 'date:Y-m-d',
            'quantity'   => 'integer',
        ];
    }

    /**
     * Get the doctor that owns the medication offering.
     *
     * @return BelongsTo<Doctor, $this>
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Get the drug that is being offered.
     *
     * @return BelongsTo<Drug, $this>
     */
    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }
}
