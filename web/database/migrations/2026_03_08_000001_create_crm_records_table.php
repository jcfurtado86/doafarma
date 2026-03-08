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
        Schema::create('crm_records', function (Blueprint $table): void {
            $table->id();
            $table->string('crm', 10);
            $table->string('uf', 2);
            $table->string('doctor_name')->nullable();
            $table->string('status', 30);
            $table->json('specialties')->nullable();
            $table->string('source', 20);
            $table->timestamp('verified_at');
            $table->timestamp('expires_at');
            $table->json('raw_response')->nullable();
            $table->timestamps();

            $table->unique(['crm', 'uf']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_records');
    }
};
