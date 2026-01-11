<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medication_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('receptor_id');
            $table->unsignedBigInteger('medication_offering_id');
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->foreign('receptor_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('medication_offering_id')
                ->references('id')
                ->on('medication_offerings')
                ->onDelete('cascade');

            $table->index('receptor_id');
            $table->index('medication_offering_id');
        });

        // Add partial unique index for PostgreSQL to ensure only one pending request per offering
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('
                CREATE UNIQUE INDEX idx_medication_requests_active_pending
                ON medication_requests (medication_offering_id)
                WHERE status = \'pending\'
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_requests');
    }
};
