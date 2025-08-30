<?php

declare(strict_types = 1);

use App\Models\Drug;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('can create a drug using factory', function (): void {
    $drug = Drug::factory()->create();

    expect($drug)
        ->toBeInstanceOf(Drug::class)
        ->toHaveKey('substance')
        ->toHaveKey('laboratory')
        ->toHaveKey('registration_number')
        ->toHaveKey('presentation')
        ->toHaveKey('stripe_color');

    assertDatabaseHas('drugs', [
        'id' => $drug->id,
    ]);
});

it('generates valid data via factory', function (): void {
    $drug = Drug::factory()->make();

    expect($drug->substance)->not->toBeEmpty()
        ->and($drug->laboratory)->not->toBeEmpty()
        ->and($drug->registration_number)->not->toBeEmpty()
        ->and($drug->presentation)->not->toBeEmpty();
});

it('can store a drug in the database', function (): void {
    $drugData = [
        'substance'           => 'Paracetamol',
        'laboratory'          => 'Medley',
        'registration_number' => '12345678',
        'presentation'        => 'Comprimido 500mg',
        'stripe_color'        => 'Tarja Branca',
    ];

    $drug                      = new Drug();
    $drug->substance           = $drugData['substance'];
    $drug->laboratory          = $drugData['laboratory'];
    $drug->registration_number = $drugData['registration_number'];
    $drug->presentation        = $drugData['presentation'];
    $drug->stripe_color        = $drugData['stripe_color'];
    $drug->save();

    assertDatabaseHas('drugs', $drugData);
});

it('can retrieve a drug from the database', function (): void {
    $drug = Drug::factory()->create([
        'substance'  => 'Dipirona',
        'laboratory' => 'EMS',
    ]);

    $foundDrug = Drug::find($drug->id);

    expect($foundDrug->substance)->toBe('Dipirona')
        ->and($foundDrug->laboratory)->toBe('EMS');
});

it('can update a drug', function (): void {
    $drug = Drug::factory()->create();

    $drug->substance = 'Ibuprofeno Atualizado';
    $drug->save();

    assertDatabaseHas('drugs', [
        'id'        => $drug->id,
        'substance' => 'Ibuprofeno Atualizado',
    ]);
});

it('can delete a drug', function (): void {
    $drug   = Drug::factory()->create();
    $drugId = $drug->id;

    $drug->delete();

    assertDatabaseMissing('drugs', [
        'id' => $drugId,
    ]);
});

it('enforces unique registration numbers', function (): void {
    Drug::factory()->create([
        'registration_number' => 'ABC123',
    ]);

    expect(fn () => Drug::factory()->create([
        'registration_number' => 'ABC123',
    ]))->toThrow(Exception::class);
});

it('allows null stripe color', function (): void {
    $drug = Drug::factory()->create([
        'stripe_color' => null,
    ]);

    expect($drug->stripe_color)->toBeNull();
    assertDatabaseHas('drugs', [
        'id'           => $drug->id,
        'stripe_color' => null,
    ]);
});
