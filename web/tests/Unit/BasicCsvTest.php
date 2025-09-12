<?php

declare(strict_types = 1);

it('creates and validates basic CSV file structure', function () {
    $csvPath = createTestCsv(
        ['id', 'name', 'email'],
        [
            ['1', 'John', 'john@example.com'],
            ['2', 'Maria', 'maria@example.com'],
        ]
    );

    expect($csvPath)->toBeValidCsv()
        ->and($csvPath)->toHaveCsvHeader(['id', 'name', 'email'])
        ->and($csvPath)->toHaveCsvRowCount(2);
});

it('validates specific CSV row content', function () {
    $csvPath = createTestCsv(
        ['id', 'name', 'email'],
        [
            ['1', 'John', 'john@example.com'],
            ['2', 'Maria', 'maria@example.com'],
        ]
    );

    expect($csvPath)->toHaveCsvRow(['id' => '1', 'name' => 'John', 'email' => 'john@example.com'])
        ->and($csvPath)->toHaveCsvRow(['id' => '1', 'email' => 'john@example.com'], 0)
        ->and($csvPath)->toHaveCsvRow(['id' => '2', 'name' => 'Maria', 'email' => 'maria@example.com'])
        ->and($csvPath)->toHaveCsvRow(['id' => '2', 'name' => 'Maria'], 1);
});

it('supports adding rows to existing CSV files', function () {
    $csvPath = createTestCsv(['id', 'name', 'email'], [['1', 'John', 'john@example.com']]);

    addCsvRows($csvPath, [['2', 'Peter', 'peter@example.com']]);

    expect($csvPath)->toHaveCsvRowCount(2);

    $data = readTestCsv($csvPath);
    expect($data)->toHaveCount(2);
});

it('handles CSV validation errors gracefully', function () {
    $invalidPath = '/non/existent/file.csv';

    expect(fn () => expect($invalidPath)->toBeValidCsv())
        ->toThrow(PHPUnit\Framework\AssertionFailedError::class);
});

it('supports partial row matching', function () {
    $csvPath = createTestCsv(
        ['id', 'name', 'email', 'status'],
        [
            ['1', 'John', 'john@example.com', 'active'],
            ['2', 'Maria', 'maria@example.com', 'inactive'],
        ]
    );

    expect($csvPath)->toHaveCsvRow(['name' => 'John', 'status' => 'active'])
        ->and($csvPath)->toHaveCsvRow(['name' => 'Maria', 'status' => 'inactive']);
});

it('creates and validates drug CSV structure', function () {
    $csvPath = createDrugsCsv(3, ['stripe_color' => 'red']);

    expect($csvPath)->toBeValidCsv()
        ->and($csvPath)->toHaveCsvRowCount(3)
        ->and($csvPath)->toHaveCsvHeader(['substance', 'laboratory', 'registration_number', 'presentation', 'stripe_color']);

    expect(validateDrugsCsvStructure($csvPath))->toBeTrue();
});
