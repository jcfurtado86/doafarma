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
        // Add status field
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->after('role');
            $table->timestamp('status_changed_at')->nullable()->after('status');
            $table->foreignId('status_changed_by')->nullable()->after('status_changed_at')
                ->constrained('users')->nullOnDelete();
        });

        // Update existing users to approved (legacy users)
        DB::table('users')->update(['status' => 'approved']);

        // Update role to allow admin (only for PostgreSQL)
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['doctor'::text, 'receptor'::text, 'admin'::text]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert role enum (only for PostgreSQL)
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['doctor'::text, 'receptor'::text]))");
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['status_changed_by']);
            $table->dropColumn(['status', 'status_changed_at', 'status_changed_by']);
        });
    }
};
