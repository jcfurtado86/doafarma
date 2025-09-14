<?php

declare(strict_types = 1);

namespace Tests\Support\Helpers;

use Tests\Support\Builders\CsvTestBuilder;

/**
 * Helper class for creating common CSV test scenarios.
 *
 * Provides convenient static methods for creating frequently used
 * CSV test files without needing to use the builder pattern directly.
 */
class CsvTestHelpers
{
    /**
     * Create a CSV file with a single drug record.
     *
     * @param  array<string, mixed>  $attributes  Drug attributes to override defaults
     * @param  int  $headerOffset  Number of metadata lines before header
     * @return string The path to the created CSV file
     */
    public static function createSingleDrugCsv(array $attributes = [], int $headerOffset = 41): string
    {
        return CsvTestBuilder::create()
            ->withHeaderOffset($headerOffset)
            ->addDrug($attributes)
            ->build();
    }

    /**
     * Create a CSV file with multiple drug records.
     *
     * @param  array<array<string, mixed>>  $drugs  Array of drug attribute arrays
     * @param  int  $headerOffset  Number of metadata lines before header
     * @return string The path to the created CSV file
     */
    public static function createMultipleDrugsCsv(array $drugs, int $headerOffset = 41): string
    {
        $builder = CsvTestBuilder::create()->withHeaderOffset($headerOffset);

        foreach ($drugs as $drug) {
            $builder->addDrug($drug);
        }

        return $builder->build();
    }

    /**
     * Create a malformed CSV file for error testing.
     *
     * Creates a file with invalid CSV content that cannot be parsed properly.
     *
     * @return string The path to the created malformed file
     */
    public static function createMalformedCsv(): string
    {
        $filename = sys_get_temp_dir() . '/malformed_' . uniqid() . '.csv';
        file_put_contents($filename, "This is not a valid CSV\nIt has malformed data\n");
        $GLOBALS['pest_temp_files'][] = $filename;

        return $filename;
    }

    /**
     * Create an empty CSV file for testing edge cases.
     *
     * @return string The path to the created empty file
     */
    public static function createEmptyCsv(): string
    {
        $filename = sys_get_temp_dir() . '/empty_' . uniqid() . '.csv';
        file_put_contents($filename, '');
        $GLOBALS['pest_temp_files'][] = $filename;

        return $filename;
    }
}
