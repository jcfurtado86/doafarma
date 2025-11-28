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
        Schema::create('drugs', function (Blueprint $table): void {
            $table->id();
            $table->string('substance', 1000);
            $table->string('laboratory');
            $table->string('registration_number')->unique();
            $table->string('product_name');
            $table->string('presentation', 1000);
            $table->string('stripe_color')->nullable();
            $table->timestamps();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE INDEX drugs_search_idx ON drugs USING GIN(to_tsvector('portuguese', product_name || ' ' || substance))"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drugs');
    }
};
