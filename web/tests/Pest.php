<?php

declare(strict_types = 1);

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Unit', 'Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

// CSV Testing Extensions for Pest 4
expect()->extend('toBeValidCsv', function (): object {
    PHPUnit\Framework\Assert::assertFileExists($this->value, "CSV file {$this->value} does not exist.");

    try {
        $csv = League\Csv\Reader::createFromPath($this->value, 'r');
        $csv->setHeaderOffset(0);
        $csv->setEscape('');
        $header = $csv->getHeader();

        PHPUnit\Framework\Assert::assertIsArray($header, "Could not read CSV header");

        return $this;
    } catch (Exception $e) {
        PHPUnit\Framework\Assert::fail("File {$this->value} is not a valid CSV: {$e->getMessage()}");
    }
});

expect()->extend('toHaveCsvHeader', function (array $expectedHeader): object {
    PHPUnit\Framework\Assert::assertFileExists($this->value, "CSV file {$this->value} does not exist.");

    try {
        $csv = League\Csv\Reader::createFromPath($this->value, 'r');
        $csv->setHeaderOffset(0);
        $csv->setEscape('');
        $header = $csv->getHeader();

        $missingColumns = array_diff($expectedHeader, $header);
        PHPUnit\Framework\Assert::assertEmpty(
            $missingColumns,
            "CSV header is missing columns: " . implode(', ', $missingColumns) .
            ". Actual header: " . json_encode($header)
        );

        $extraColumns = array_diff($header, $expectedHeader);
        PHPUnit\Framework\Assert::assertEmpty(
            $extraColumns,
            "CSV header contains extra columns: " . implode(', ', $extraColumns) .
            ". Actual header: " . json_encode($header)
        );

        return $this;
    } catch (Exception $e) {
        PHPUnit\Framework\Assert::fail("Error reading CSV: {$e->getMessage()}");
    }
});

expect()->extend('toHaveCsvRowCount', function (int $expectedCount): object {
    PHPUnit\Framework\Assert::assertFileExists($this->value, "CSV file {$this->value} does not exist.");

    try {
        $csv = League\Csv\Reader::createFromPath($this->value, 'r');
        $csv->setHeaderOffset(0);
        $csv->setEscape('');

        $actualCount = iterator_count($csv->getRecords());

        PHPUnit\Framework\Assert::assertEquals(
            $expectedCount,
            $actualCount,
            "CSV has {$actualCount} rows, but expected {$expectedCount}."
        );

        return $this;
    } catch (Exception $e) {
        PHPUnit\Framework\Assert::fail("Error reading CSV: {$e->getMessage()}");
    }
});

expect()->extend('toHaveCsvRow', function (array $expectedRow, int | null $index = null): object {
    PHPUnit\Framework\Assert::assertFileExists($this->value, "CSV file {$this->value} does not exist.");

    try {
        $csv = League\Csv\Reader::createFromPath($this->value, 'r');
        $csv->setHeaderOffset(0);
        $csv->setEscape('');

        // Re-index the array to start from 0
        $records = array_values(iterator_to_array($csv->getRecords()));

        if ($index !== null) {
            PHPUnit\Framework\Assert::assertArrayHasKey(
                $index,
                $records,
                "Row {$index} does not exist in CSV."
            );

            $actualRecord = $records[$index];

            $rowMatches = csvRowMatches($actualRecord, $expectedRow);
            PHPUnit\Framework\Assert::assertTrue(
                $rowMatches,
                "Row {$index} does not match expected values. Expected: " . json_encode($expectedRow) .
                ". Actual: " . json_encode($actualRecord)
            );
        } else {
            $found = array_any($records, fn (array $record): bool => csvRowMatches($record, $expectedRow));
            PHPUnit\Framework\Assert::assertTrue(
                $found,
                "No CSV row matches the expected values."
            );
        }

        return $this;
    } catch (Exception $e) {
        PHPUnit\Framework\Assert::fail("Error reading CSV: {$e->getMessage()}");
    }
});

expect()->extend('toHaveCsvHeaderAtOffset', function (array $expectedHeader, int $headerOffset = 0): object {
    PHPUnit\Framework\Assert::assertFileExists($this->value, "CSV file {$this->value} does not exist.");

    try {
        $csv = League\Csv\Reader::createFromPath($this->value, 'r');
        $csv->setHeaderOffset($headerOffset);
        $csv->setEscape('');
        $header = $csv->getHeader();

        $missingColumns = array_diff($expectedHeader, $header);
        PHPUnit\Framework\Assert::assertEmpty(
            $missingColumns,
            "CSV header is missing columns: " . implode(', ', $missingColumns) .
            ". Actual header: " . json_encode($header)
        );

        $extraColumns = array_diff($header, $expectedHeader);
        PHPUnit\Framework\Assert::assertEmpty(
            $extraColumns,
            "CSV header contains extra columns: " . implode(', ', $extraColumns) .
            ". Actual header: " . json_encode($header)
        );

        return $this;
    } catch (Exception $e) {
        PHPUnit\Framework\Assert::fail("Error reading CSV: {$e->getMessage()}");
    }
});

expect()->extend('toHaveCsvRowCountAtOffset', function (int $expectedCount, int $headerOffset = 0): object {
    PHPUnit\Framework\Assert::assertFileExists($this->value, "CSV file {$this->value} does not exist.");

    try {
        $csv = League\Csv\Reader::createFromPath($this->value, 'r');
        $csv->setHeaderOffset($headerOffset);
        $csv->setEscape('');

        $actualCount = iterator_count($csv->getRecords());

        PHPUnit\Framework\Assert::assertEquals(
            $expectedCount,
            $actualCount,
            "CSV has {$actualCount} rows, but expected {$expectedCount}."
        );

        return $this;
    } catch (Exception $e) {
        PHPUnit\Framework\Assert::fail("Error reading CSV: {$e->getMessage()}");
    }
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}

// Global state for temporary file cleanup
$GLOBALS['pest_temp_files'] = [];

/**
 * Creates a temporary CSV file for testing.
 */
function createTestCsv(array $header, array $rows = [], string | null $filename = null): string
{
    $filename ??= sys_get_temp_dir() . '/pest_csv_' . uniqid() . '.csv';

    $csv = League\Csv\Writer::createFromPath($filename, 'w+');
    $csv->insertOne($header);

    if ($rows !== []) {
        $csv->insertAll($rows);
    }

    $GLOBALS['pest_temp_files'][] = $filename;

    return $filename;
}

/**
 * Appends rows to an existing CSV file.
 */
function addCsvRows(string $csvPath, array $rows): string
{
    if (! file_exists($csvPath)) {
        throw new InvalidArgumentException("CSV file {$csvPath} does not exist.");
    }

    $csv = League\Csv\Writer::createFromPath($csvPath, 'a+');
    $csv->insertAll($rows);

    return $csvPath;
}

/**
 * Reads CSV content as associative arrays.
 */
function readTestCsv(string $csvPath): array
{
    if (! file_exists($csvPath)) {
        throw new InvalidArgumentException("CSV file {$csvPath} does not exist.");
    }

    $csv = League\Csv\Reader::createFromPath($csvPath, 'r');
    $csv->setHeaderOffset(0);
    $csv->setEscape('');

    return array_values(iterator_to_array($csv->getRecords()));
}

/**
 * Checks if a CSV record matches expected values (partial matching supported).
 */
function csvRowMatches(array $record, array $expectedRow): bool
{
    return array_all($expectedRow, fn ($value, $key): bool => isset($record[$key]) && $record[$key] === $value);
}

function pest_cleanup_temp_files(): void
{
    if (isset($GLOBALS['pest_temp_files'])) {
        foreach ($GLOBALS['pest_temp_files'] as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $GLOBALS['pest_temp_files'] = [];
    }
}

afterEach(function (): void {
    pest_cleanup_temp_files();
});
