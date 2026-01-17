<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert existing 'scheduled' status to 'proposed'
        DB::table('medication_appointments')
            ->where('status', 'scheduled')
            ->update(['status' => 'proposed']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back 'proposed' to 'scheduled'
        DB::table('medication_appointments')
            ->where('status', 'proposed')
            ->update(['status' => 'scheduled']);
    }
};
