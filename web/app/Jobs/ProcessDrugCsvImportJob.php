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

            $this->hasRequiredHeaders($csv);

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
        $delimiter = $this->detectDelimiter($this->filepath);

        $csv = Reader::createFromPath($this->filepath, 'r');
        $csv->setEscape('');
        $csv->setDelimiter($delimiter);
        $csv->setHeaderOffset(self::HEADER_OFFSET);

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
                    'product_name' => trim($record['PRODUTO'] ?? ''),
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
        try {
            $rawHeader = $csv->getHeader();
            $headers   = array_map(fn (string $h) => mb_strtoupper(trim($h)), $rawHeader);

            $missing = array_values(array_diff(self::REQUIRED_HEADERS, $headers));

            if ($missing !== []) {
                $errorMessage = 'Missing required CSV columns: ' . implode(', ', $missing);

                Log::error($errorMessage, [
                    'file'     => $this->filepath,
                    'headers'  => $rawHeader,
                    'expected' => self::REQUIRED_HEADERS,
                ]);

                throw new \InvalidArgumentException($errorMessage);
            }

            Log::info('CSV headers validated successfully', [
                'header_count'   => count($headers),
                'delimiter_used' => $csv->getDelimiter(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to read CSV headers', [
                'file'  => $this->filepath,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
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
            && $this->hasValidLaboratorio($record)
            && $this->hasValidProduto($record);
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
     * Validates the 'PRODUTO' field of a drug record.
     *
     * Checks if the 'PRODUTO' field exists and is not empty or '0'.
     *
     * @param array<string> $record The drug record data from the CSV.
     * @return bool Returns true if 'PRODUTO' is valid; false otherwise.
     */
    private function hasValidProduto(array $record): bool
    {
        return isset($record['PRODUTO']) && ! in_array(trim($record['PRODUTO']), ['', '0'], true);
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

    /**
     * Detects the appropriate delimiter for the CSV file using League\Csv.
     *
     * @param string $filepath Path to the CSV file
     * @return string The detected delimiter (';' or ',')
     */
    private function detectDelimiter(string $filepath): string
    {
        $semicolonReader = Reader::createFromPath($filepath, 'r');
        $semicolonReader->setEscape('');
        $semicolonReader->setDelimiter(';');
        $semicolonReader->setHeaderOffset(self::HEADER_OFFSET);

        try {
            $semicolonHeader = $semicolonReader->getHeader();
            $semicolonCount  = count($semicolonHeader);

            Log::info('Delimiter detection with semicolon', [
                'header_count'  => $semicolonCount,
                'first_columns' => array_slice($semicolonHeader, 0, 3),
            ]);

            if ($this->containsRequiredHeaders($semicolonHeader)) {
                return ';';
            }
        } catch (\Exception $e) {
            Log::warning('Failed to read CSV with semicolon delimiter', ['error' => $e->getMessage()]);
        }

        // fallback
        $commaReader = Reader::createFromPath($filepath, 'r');
        $commaReader->setEscape('');
        $commaReader->setDelimiter(',');
        $commaReader->setHeaderOffset(self::HEADER_OFFSET);

        try {
            $commaHeader = $commaReader->getHeader();
            $commaCount  = count($commaHeader);

            Log::info('Delimiter detection with comma', [
                'header_count'  => $commaCount,
                'first_columns' => array_slice($commaHeader, 0, 3),
            ]);

            if ($this->containsRequiredHeaders($commaHeader)) {
                return ',';
            }
        } catch (\Exception $e) {
            Log::warning('Failed to read CSV with comma delimiter', ['error' => $e->getMessage()]);
        }

        Log::warning('Could not detect delimiter reliably, defaulting to semicolon');

        return ';';
    }

    /**
     * Checks if the header contains the required columns for drug import.
     *
     * @param array<string> $header The header array from CSV
     * @return bool True if header contains required columns
     */
    private function containsRequiredHeaders(array $header): bool
    {
        $normalizedHeader = array_map(fn (string $h) => mb_strtoupper(trim($h)), $header);
        $missing          = array_diff(self::REQUIRED_HEADERS, $normalizedHeader);

        return empty($missing);
    }
}
