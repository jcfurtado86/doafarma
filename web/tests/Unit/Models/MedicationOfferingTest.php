<?php

declare(strict_types = 1);

use App\Models\MedicationOffering;
use Carbon\Carbon;

it('casts expires_at to a Carbon instance', function (): void {
    $offering = MedicationOffering::factory()->make();

    expect($offering->expires_at)->toBeInstanceOf(Carbon::class);
});

it('casts quantity to an integer', function (): void {
    $offering = MedicationOffering::factory()->make(['quantity' => '50']);

    expect($offering->quantity)->toBeInt()->toBe(50);
});
