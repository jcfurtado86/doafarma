<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Add index on quantity column for search performance.
     *
     * The SearchMedicationOfferingsAction uses WHERE quantity > 0
     * in 100% of searches. Without this index, PostgreSQL does a
     * full table scan, degrading performance significantly with
     * large datasets (3-5s with 50k+ records).
     */
    public function up(): void
    {
        Schema::table('medication_offerings', function (Blueprint $table): void {
            $table->index('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('medication_offerings', function (Blueprint $table): void {
            $table->dropIndex(['quantity']);
        });
    }
};
