<?php

declare(strict_types = 1);

use App\Jobs\ProcessDrugCsvImportJob;
use App\Models\Drug;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\assertDatabaseHas;

use Tests\Support\Builders\CsvTestBuilder;

use Tests\Support\Helpers\CsvTestHelpers;

beforeEach(function (): void {
    Log::spy();
});

describe('ProcessDrugCsvImportJob', function (): void {
    it('processes valid CSV file with header offset 41', function (): void {
        // Arrange
        $csvPath = CsvTestBuilder::create()
            ->withHeaderOffset(41)
            ->withDelimiter(';')
            ->addDrug([
                'SUBSTÂNCIA'   => 'PARACETAMOL',
                'LABORATÓRIO'  => 'EMS S.A.',
                'REGISTRO'     => '1234567890123',
                'PRODUTO'      => 'TYLENOL',
                'APRESENTAÇÃO' => '500 MG COM CT BL AL X 20',
                'TARJA'        => 'SEM TARJA',
            ])
            ->addDrug([
                'SUBSTÂNCIA'   => 'DIPIRONA',
                'LABORATÓRIO'  => 'EUROFARMA',
                'REGISTRO'     => '9876543210987',
                'PRODUTO'      => 'NOVALGINA',
                'APRESENTAÇÃO' => '500 MG COM CT BL AL X 30',
                'TARJA'        => 'TARJA VERMELHA',
            ])
            ->addDrug([
                'SUBSTÂNCIA'   => 'IBUPROFENO',
                'LABORATÓRIO'  => 'MEDLEY',
                'REGISTRO'     => '5555666677778',
                'PRODUTO'      => 'ADVIL',
                'APRESENTAÇÃO' => '200 MG COM CT BL AL X 12',
                'TARJA'        => 'TARJA PRETA',
            ])
            ->build();

        // Act
        $job = new ProcessDrugCsvImportJob($csvPath);
        $job->handle();

        // Assert
        assertDatabaseHas('drugs', [
            'substance'           => 'PARACETAMOL',
            'laboratory'          => 'EMS S.A.',
            'registration_number' => '1234567890123',
            'product_name'        => 'TYLENOL',
            'presentation'        => '500 MG COM CT BL AL X 20',
            'stripe_color'        => 'Sem Tarja',
        ]);

        expect(Drug::count())->toBe(3);
    });

    it('detects semicolon delimiter correctly using League CSV', function (): void {
        // Arrange
        $csvPath = CsvTestBuilder::create()
            ->withHeaderOffset(41)
            ->withDelimiter(';')
            ->addDrug([
                'SUBSTÂNCIA'   => 'PARACETAMOL',
                'LABORATÓRIO'  => 'EMS S.A.',
                'REGISTRO'     => '1234567890',
                'PRODUTO'      => 'PRODUTO COM, VÍRGULA',
                'APRESENTAÇÃO' => '17,5% SOL INJ',
                'TARJA'        => 'TARJA VERMELHA',
            ])
            ->build();

        // Act
        $job          = new ProcessDrugCsvImportJob($csvPath);
        $reflection   = new ReflectionClass($job);
        $detectMethod = $reflection->getMethod('detectDelimiter');
        $detectMethod->setAccessible(true);

        $detectedDelimiter = $detectMethod->invoke($job, $csvPath);

        // Assert
        expect($detectedDelimiter)->toBe(';');

        $job->handle();
        expect(Drug::count())->toBe(1);
    });

    it('logs warning for invalid rows but continues processing', function (): void {
        // Arrange
        $csvPath = CsvTestBuilder::create()
            ->withHeaderOffset(41)
            ->withDelimiter(';')
            ->addDrug([
                'SUBSTÂNCIA'   => 'PARACETAMOL',
                'LABORATÓRIO'  => 'Valid Lab',
                'REGISTRO'     => '12345678',
                'PRODUTO'      => 'TYLENOL',
                'APRESENTAÇÃO' => 'Valid Presentation',
                'TARJA'        => 'SEM TARJA',
            ])
            ->addEmptyRow() // Invalid - empty row
            ->addInvalidRow() // Invalid - non-numeric REGISTRO
            ->addDrug([
                'SUBSTÂNCIA'   => 'DIPIRONA',
                'LABORATÓRIO'  => 'Another Lab',
                'REGISTRO'     => '87654321',
                'PRODUTO'      => 'NOVALGINA',
                'APRESENTAÇÃO' => 'Another Presentation',
                'TARJA'        => 'TARJA VERMELHA',
            ])
            ->build();

        // Act
        $job = new ProcessDrugCsvImportJob($csvPath);
        $job->handle();

        // Assert
        Log::shouldHaveReceived('warning')
            ->atLeast()
            ->once()
            ->with(
                Mockery::on(fn ($message): bool => str_contains($message, 'Invalid drug record skipped')),
                Mockery::any()
            );

        expect(Drug::count())->toBe(2);

        assertDatabaseHas('drugs', [
            'substance'           => 'PARACETAMOL',
            'registration_number' => '12345678',
        ]);

        assertDatabaseHas('drugs', [
            'substance'           => 'DIPIRONA',
            'registration_number' => '87654321',
        ]);
    });

    it('logs error and fails gracefully with malformed CSV', function (): void {
        // Arrange
        $malformedPath = CsvTestHelpers::createMalformedCsv();

        // Act
        $job = new ProcessDrugCsvImportJob($malformedPath);
        $job->handle();

        // Assert
        Log::shouldHaveReceived('error')
            ->atLeast()
            ->once()
            ->with(
                Mockery::on(fn ($message): bool => str_contains($message, 'Fatal error processing CSV file:')),
                Mockery::any()
            );

        expect(Drug::count())->toBe(0);
    });

    it('normalizes stripe colors correctly', function (): void {
        // Arrange
        $csvPath = CsvTestBuilder::create()
            ->withHeaderOffset(41)
            ->withDelimiter(';')
            ->addDrug(['TARJA' => 'TARJA VERMELHA'])
            ->addDrug(['TARJA' => 'TARJA VERMELHA (**)'])
            ->addDrug(['TARJA' => 'TARJA PRETA'])
            ->addDrug(['TARJA' => 'SEM TARJA'])
            ->addDrug(['TARJA' => '- (*)'])
            ->build();

        // Act
        $job = new ProcessDrugCsvImportJob($csvPath);
        $job->handle();

        // Assert
        assertDatabaseHas('drugs', ['stripe_color' => 'Vermelha']);
        assertDatabaseHas('drugs', ['stripe_color' => 'Vermelha - Controle Especial']);
        assertDatabaseHas('drugs', ['stripe_color' => 'Preta']);
        assertDatabaseHas('drugs', ['stripe_color' => 'Sem Tarja']);
        assertDatabaseHas('drugs', ['stripe_color' => 'Não aplicável']);
    });

    it('updates existing drugs instead of creating duplicates', function (): void {
        // Arrange
        $existingDrug = Drug::factory()->create([
            'registration_number' => '123456',
            'substance'           => 'Old Substance',
        ]);

        $csvPath = CsvTestBuilder::create()
            ->withHeaderOffset(41)
            ->withDelimiter(';')
            ->addDrug([
                'REGISTRO'   => '123456',
                'SUBSTÂNCIA' => 'Updated Substance',
            ])
            ->build();

        // Act
        $job = new ProcessDrugCsvImportJob($csvPath);
        $job->handle();

        // Assert
        expect(Drug::count())->toBe(1);
        assertDatabaseHas('drugs', [
            'id'                  => $existingDrug->id,
            'registration_number' => '123456',
            'substance'           => 'Updated Substance',
        ]);
    });

    it('processes large CSV files efficiently', function (): void {
        // Arrange
        $csvPath = CsvTestBuilder::create()
            ->withHeaderOffset(41)
            ->withDelimiter(';')
            ->addDrugs(10000) // Adiciona 10000 drugs com dados aleatórios
            ->build();

        // Act
        $startTime = microtime(true);
        $job       = new ProcessDrugCsvImportJob($csvPath);
        $job->handle();
        $endTime = microtime(true);

        // Assert
        expect(Drug::count())->toBe(10000);
        expect($endTime - $startTime)->toBeLessThan(10); // Should process in under 10 seconds
    });

    it('handles missing required columns gracefully', function (): void {
        // Arrange
        $csvPath = CsvTestBuilder::create()
            ->withHeader(['SUBSTÂNCIA', 'LABORATÓRIO']) // Missing required columns
            ->withHeaderOffset(41)
            ->withDelimiter(';')
            ->addCustomRow(['PARACETAMOL', 'EMS'])
            ->build();

        // Act
        $job = new ProcessDrugCsvImportJob($csvPath);
        $job->handle();

        // Assert
        Log::shouldHaveReceived('error')->atLeast()->once();
        expect(Drug::count())->toBe(0);
    });
});
