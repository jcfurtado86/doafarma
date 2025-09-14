<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Jobs\ProcessDrugCsvImportJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportDrugsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drugs:import {filepath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import drugs from a specified CSV file';

    /**
     * Execute the console command.
     */
    public function handle(): ?int
    {
        $filepath = $this->argument('filepath');

        if (! File::exists($filepath)) {
            $this->error("File not found: $filepath");

            return 1;
        }

        $this->info("File found! The import job has been dispatched to the queue.");
        $this->info("Monitor the queue and logs to track the progress.");

        ProcessDrugCsvImportJob::dispatch($filepath);

        return null;
    }
}
