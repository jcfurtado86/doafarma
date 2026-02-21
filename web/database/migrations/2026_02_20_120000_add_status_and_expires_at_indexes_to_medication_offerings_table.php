<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Add indexes on status and expires_at columns for query performance.
     *
     * These indexes optimize:
     * - Model scopes: scopeAvailable(), scopeReserved(), scopeCompleted()
     * - Filament filters: expiring soon, expired offerings
     * - API list/search queries that filter by status
     *
     * The composite index (status, expires_at) is particularly useful for
     * queries like "WHERE status = 'available' AND expires_at > NOW()"
     * which are common in the medication search flow.
     */
    public function up(): void
    {
        Schema::table('medication_offerings', function (Blueprint $table): void {
            $table->index('status');
            $table->index('expires_at');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('medication_offerings', function (Blueprint $table): void {
            $table->dropIndex(['status', 'expires_at']);
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['status']);
        });
    }
};
