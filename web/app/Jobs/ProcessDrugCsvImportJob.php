<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\Drug;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;

class ProcessDrugCsvImportJob implements ShouldQueue
{
    use Queueable;

    private const int HEADER_OFFSET       = 41;
    private const int MIN_REGISTRO_LENGTH = 5;
    private const int MAX_REGISTRO_LENGTH = 20;
    private const array REQUIRED_HEADERS  = [
        'SUBSTÂNCIA',
        'LABORATÓRIO',
        'REGISTRO',
        'PRODUTO',
        'APRESENTAÇÃO',
        'TARJA',
    ];

    public function __construct(
        private readonly string $filepath,
    ) {
        //
    }

    public function handle(): void
    {
        try {
            $csv = $this->createCsvReader();

            if (! $this->hasRequiredHeaders($csv)) {
                return;
            }

            $records = $csv->getRecords();

            foreach ($records as $record) {
                $this->processRecord($record);
            }
        } catch (\Exception $e) {
            Log::error('Fatal error processing CSV file: ' . $this->filepath, [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Creates and configures a CSV reader instance for processing drug import files.
     *
     * @return Reader<string[]> Returns a configured CSV reader instance.
     */
    private function createCsvReader(): Reader
    {
        $csv = Reader::createFromPath($this->filepath, 'r');
        $csv->setHeaderOffset(self::HEADER_OFFSET);
        $csv->setEscape('');

        return $csv;
    }

    /**
     * Processes a single drug record from the CSV import.
     *
     * @param array<string> $record The drug record data from the CSV.
     */
    private function processRecord(array $record): void
    {
        try {
            if (! $this->isValidDrugRecord($record)) {
                Log::warning('Invalid drug record skipped', ['record' => $record]);

                return;
            }

            Drug::updateOrCreate(
                ['registration_number' => trim((string) $record['REGISTRO'])],
                [
                    'substance'    => trim($record['SUBSTÂNCIA'] ?? ''),
                    'laboratory'   => trim($record['LABORATÓRIO'] ?? ''),
                    'presentation' => trim($record['APRESENTAÇÃO'] ?? ''),
                    'stripe_color' => $this->normalizeStripeColor($record['TARJA'] ?? ''),
                ]
            );
        } catch (\Exception $e) {
            Log::warning('Failed to process drug record', [
                'registration_number' => $record['REGISTRO'] ?? 'UNKNOWN',
                'error'               => $e->getMessage(),
                'record'              => $record,
            ]);
        }
    }

    /**
     * Checks if the CSV file contains all required header columns for drug import.
     *
     * @param Reader<string[]> $csv The CSV reader instance.
     * @return bool Returns true if all required headers are present, false otherwise.
     */
    private function hasRequiredHeaders(Reader $csv): bool
    {
        $rawHeader = $csv->getHeader();
        $headers   = array_map(fn (string $h) => mb_strtoupper(trim($h)), $rawHeader);

        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $headers));

        if ($missing !== []) {
            Log::error('Missing required CSV columns: ' . implode(', ', $missing), [
                'file'    => $this->filepath,
                'headers' => $rawHeader,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Validates whether a drug record from the CSV contains all required fields.
     *
     * @param array<string> $record The drug record data from the CSV.
     * @return bool Returns true if the record has valid 'REGISTRO', 'SUBSTÂNCIA', and 'LABORATÓRIO' fields; false otherwise.
     */
    private function isValidDrugRecord(array $record): bool
    {
        return $this->hasValidRegistro($record)
            && $this->hasValidSubstancia($record)
            && $this->hasValidLaboratorio($record);
    }

    /**
     * Validates the 'REGISTRO' field of a drug record.
     *
     * Checks if the 'REGISTRO' field exists, is not empty or '0', is numeric, and its length is within the allowed range.
     *
     * @param array<string> $record The drug record data from the CSV.
     * @return bool Returns true if 'REGISTRO' is valid; false otherwise.
     */
    private function hasValidRegistro(array $record): bool
    {
        if (! isset($record['REGISTRO']) || in_array(trim($record['REGISTRO']), ['', '0'], true)) {
            return false;
        }

        $registro = trim($record['REGISTRO']);

        return $this->isNumeric($registro)
            && $this->hasValidLength($registro);
    }

    /**
     * Checks if a given string consists only of numeric digits.
     *
     * @param string $value The value to check.
     * @return bool Returns true if the value contains only digits; false otherwise.
     */
    private function isNumeric(string $value): bool
    {
        return ctype_digit($value);
    }

    /**
     * Checks if the length of a given string is within the allowed range for 'REGISTRO'.
     *
     * @param string $value The value whose length will be validated.
     * @return bool Returns true if the length is between MIN_REGISTRO_LENGTH and MAX_REGISTRO_LENGTH; false otherwise.
     */
    private function hasValidLength(string $value): bool
    {
        $length = strlen($value);

        return $length >= self::MIN_REGISTRO_LENGTH && $length <= self::MAX_REGISTRO_LENGTH;
    }

    /**
     * Validates the 'SUBSTÂNCIA' field of a drug record.
     *
     * Checks if the 'SUBSTÂNCIA' field exists and is not empty or '0'.
     *
     * @param array<string> $record The drug record data from the CSV.
     * @return bool Returns true if 'SUBSTÂNCIA' is valid; false otherwise.
     */
    private function hasValidSubstancia(array $record): bool
    {
        return isset($record['SUBSTÂNCIA']) && ! in_array(trim($record['SUBSTÂNCIA']), ['', '0'], true);
    }

    /**
     * Validates the 'LABORATÓRIO' field of a drug record.
     *
     * Checks if the 'LABORATÓRIO' field exists and is not empty or '0'.
     *
     * @param array<string> $record The drug record data from the CSV.
     * @return bool Returns true if 'LABORATÓRIO' is valid; false otherwise.
     */
    private function hasValidLaboratorio(array $record): bool
    {
        return isset($record['LABORATÓRIO']) && ! in_array(trim($record['LABORATÓRIO']), ['', '0'], true);
    }

    /**
     * Normalizes the 'TARJA' (stripe color) field of a drug record.
     *
     * Converts the input to uppercase, trims whitespace, and maps known values to standardized labels.
     * Returns null for empty, '0', or null values.
     *
     * @param string|null $tarja The raw 'TARJA' value from the CSV.
     * @return string|null Returns the normalized stripe color label, or null if not applicable.
     */
    private function normalizeStripeColor(?string $tarja): ?string
    {
        if ($tarja === null || $tarja === '' || $tarja === '0') {
            return null;
        }

        $tarja = trim(strtoupper($tarja));

        return match ($tarja) {
            'TARJA VERMELHA'      => 'Vermelha',
            'TARJA VERMELHA (**)' => 'Vermelha - Controle Especial',
            'TARJA PRETA'         => 'Preta',
            '- (*)'               => 'Não aplicável',
            'SEM TARJA'           => 'Sem Tarja',
            default               => $tarja,
        };
    }
}
