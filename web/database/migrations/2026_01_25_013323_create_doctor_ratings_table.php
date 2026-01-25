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
        Schema::create('doctor_ratings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('medication_appointment_id')->unique();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('receptor_id');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('medication_appointment_id')
                ->references('id')
                ->on('medication_appointments')
                ->onDelete('cascade');

            $table->foreign('doctor_id')
                ->references('id')
                ->on('doctors')
                ->onDelete('cascade');

            $table->foreign('receptor_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->index('doctor_id');
            $table->index('receptor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_ratings');
    }
};
