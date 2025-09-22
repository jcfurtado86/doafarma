<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Drug extends Model
{
    /** @use HasFactory<\Database\Factories\DrugFactory> */
    use HasFactory;

    protected $fillable = [
        'substance',
        'laboratory',
        'registration_number',
        'product_name',
        'presentation',
        'stripe_color',
    ];

    /**
     * Get the medication offerings for the doctor.
     *
     * @return HasMany<MedicationOffering, $this>
     */
    public function medicationOfferings(): HasMany
    {
        return $this->hasMany(MedicationOffering::class);
    }
}
