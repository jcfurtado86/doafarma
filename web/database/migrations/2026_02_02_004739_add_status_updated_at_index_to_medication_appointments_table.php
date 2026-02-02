<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Add composite index on status + updated_at for history queries.
     *
     * The ListDoctorDonationHistoryAction and ListReceptorHistoryAction
     * use WHERE status = 'completed' ORDER BY updated_at DESC.
     * This composite index allows PostgreSQL to filter AND sort
     * in a single index scan, avoiding expensive filesort operations.
     */
    public function up(): void
    {
        Schema::table('medication_appointments', function (Blueprint $table): void {
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::table('medication_appointments', function (Blueprint $table): void {
            $table->dropIndex(['status', 'updated_at']);
        });
    }
};
