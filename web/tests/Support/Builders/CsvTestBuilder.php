<?php

declare(strict_types = 1);

namespace Tests\Support\Builders;

use League\Csv\Writer;

/**
 * Builder pattern for creating CSV test files with drug data.
 *
 * This class provides a fluent interface to create CSV files for testing
 * drug import functionality with customizable headers, offsets, and data.
 */
class CsvTestBuilder
{
    private array $header = ['SUBSTÂNCIA', 'LABORATÓRIO', 'REGISTRO', 'PRODUTO', 'APRESENTAÇÃO', 'TARJA'];

    private array $rows = [];

    private int $headerOffset = 41;

    private string $delimiter = ',';

    /**
     * Create a new CSV test builder instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set a custom header for the CSV file.
     *
     * @param  array<string>  $header
     */
    public function withHeader(array $header): self
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Set the header offset (number of metadata lines before the actual header).
     *
     * @param  int  $offset  Number of lines to skip before the header
     */
    public function withHeaderOffset(int $offset): self
    {
        $this->headerOffset = $offset;

        return $this;
    }

    /**
     * Set a custom delimiter for the CSV file.
     *
     * @param  string  $delimiter  The delimiter used in the CSV file
     */
    public function withDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    /**
     * Add a single drug record to the CSV.
     *
     * @param  array<string, mixed>  $attributes  Drug attributes to override defaults
     */
    public function addDrug(array $attributes = []): self
    {
        $defaults = [
            'SUBSTÂNCIA'   => fake()->randomElement(['PARACETAMOL', 'DIPIRONA', 'IBUPROFENO', 'AMOXICILINA']),
            'LABORATÓRIO'  => fake()->randomElement(['EMS S.A.', 'EUROFARMA', 'MEDLEY', 'SANOFI']),
            'REGISTRO'     => fake()->unique()->numerify('##########'),
            'PRODUTO'      => fake()->randomElement(['TYLENOL', 'NOVALGINA', 'ADVIL', 'AMOXIL']),
            'APRESENTAÇÃO' => fake()->randomElement(['500 MG COM CT BL AL X 20', '100 ML SUSP OR', '250 MG CAP GEL']),
            'TARJA'        => fake()->randomElement(['SEM TARJA', 'TARJA VERMELHA', 'TARJA PRETA']),
        ];

        $drug         = array_merge($defaults, $attributes);
        $this->rows[] = $drug;

        return $this;
    }

    /**
     * Add multiple drug records to the CSV.
     *
     * @param  int  $count  Number of drugs to add
     * @param  array<string, mixed>  $baseAttributes  Base attributes for all drugs
     */
    public function addDrugs(int $count, array $baseAttributes = []): self
    {
        for ($i = 0; $i < $count; $i++) {
            $this->addDrug($baseAttributes);
        }

        return $this;
    }

    /**
     * Add a custom row with specific data.
     *
     * @param  array<string, mixed>  $row
     */
    public function addCustomRow(array $row): self
    {
        $this->rows[] = $row;

        return $this;
    }

    /**
     * Add a drug that simulates real-world data with commas in values.
     */
    public function addRealWorldDrug(array $attributes = []): self
    {
        $defaults = [
            'SUBSTÂNCIA'   => 'PARACETAMOL, CAFEÍNA, ÁCIDO ACETILSALICÍLICO',
            'LABORATÓRIO'  => 'LABORATÓRIO TEUTO BRASILEIRO S/A',
            'REGISTRO'     => fake()->unique()->numerify('##########'),
            'PRODUTO'      => 'MEDICAMENTO COMPOSTO, 500MG',
            'APRESENTAÇÃO' => '17,5% SOL INJ CT FA VD X 1,5 ML',
            'TARJA'        => 'TARJA VERMELHA (**)',
        ];

        $drug         = array_merge($defaults, $attributes);
        $this->rows[] = $drug;

        return $this;
    }

    /**
     * Add an empty row (useful for testing validation).
     */
    public function addEmptyRow(): self
    {
        $this->rows[] = array_fill(0, count($this->header), '');

        return $this;
    }

    /**
     * Add an invalid row with non-numeric registration number.
     */
    public function addInvalidRow(): self
    {
        $this->rows[] = [
            'SUBSTÂNCIA'   => 'Invalid Drug',
            'LABORATÓRIO'  => 'Invalid Lab',
            'REGISTRO'     => 'invalid_registro', // Non-numeric
            'PRODUTO'      => 'Invalid Product',
            'APRESENTAÇÃO' => 'Invalid Presentation',
            'TARJA'        => 'Invalid Tarja',
        ];

        return $this;
    }

    /**
     * Add metadata lines before the header.
     *
     * @param integer $count Number of metadata lines to add
     */
    public function addMetadataLines(int $count = 41): self
    {
        $this->headerOffset = $count;

        return $this;
    }

    /**
     * Build the CSV file and return its path.
     *
     * Creates a temporary CSV file with the configured header offset,
     * header, and data rows.
     *
     * @return string The path to the created CSV file
     */
    public function build(): string
    {
        $filename = sys_get_temp_dir() . '/pest_drug_csv_' . uniqid() . '.csv';

        $csv = Writer::createFromPath($filename, 'w+');
        $csv->setEscape('');
        $csv->setDelimiter($this->delimiter);

        for ($i = 0; $i < $this->headerOffset; $i++) {
            $csv->insertOne(["Metadata line " . ($i + 1), "Additional info", "More data"]);
        }

        $csv->insertOne($this->header);

        if ($this->rows !== []) {
            $csv->insertAll($this->rows);
        }

        $GLOBALS['pest_temp_files'][] = $filename;

        return $filename;
    }
}
