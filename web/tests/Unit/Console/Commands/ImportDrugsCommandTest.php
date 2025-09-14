<?php

declare(strict_types = 1);

use App\Jobs\ProcessDrugCsvImportJob;
use Illuminate\Support\Facades\Bus;
use Tests\Support\Builders\CsvTestBuilder;

it('returns error when file not found', function (): void {
    $missing = sys_get_temp_dir() . '/non_existent_' . uniqid() . '.csv';

    $this->artisan('drugs:import', ['filepath' => $missing])
        ->expectsOutput("File not found: $missing")
        ->assertExitCode(1);
});

it('dispatches the import job when file exists', function (): void {
    Bus::fake();

    $csvPath = CsvTestBuilder::create()
        ->withHeaderOffset(41)
        ->addDrug() // adiciona uma linha válida
        ->build();

    $this->artisan('drugs:import', ['filepath' => $csvPath])
        ->expectsOutput('File found! The import job has been dispatched to the queue.')
        ->expectsOutput('Monitor the queue and logs to track the progress.')
        ->assertExitCode(0);

    Bus::assertDispatched(function (ProcessDrugCsvImportJob $job) use ($csvPath): bool {
        $ref = new ReflectionObject($job);

        if (! $ref->hasProperty('filepath')) {
            return false;
        }
        $prop = $ref->getProperty('filepath');

        return $prop->getValue($job) === $csvPath;
    });
});
