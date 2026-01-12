<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medication_appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('medication_request_id')->unique();
            $table->unsignedBigInteger('address_id');
            $table->date('scheduled_date');
            $table->time('scheduled_time');
            $table->string('status', 20)->default('scheduled');
            $table->boolean('receptor_confirmed')->default(false);
            $table->boolean('doctor_confirmed')->default(false);
            $table->timestamps();

            $table->foreign('medication_request_id')
                ->references('id')
                ->on('medication_requests')
                ->onDelete('cascade');

            $table->foreign('address_id')
                ->references('id')
                ->on('addresses')
                ->onDelete('restrict');

            $table->index('scheduled_date');
            $table->index(['status', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_appointments');
    }
};
