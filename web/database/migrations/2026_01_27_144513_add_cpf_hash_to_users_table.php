<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add cpf_hash column for unique validation and lookups.
 *
 * Since CPF is now encrypted, we need a deterministic hash for:
 * 1. Unique constraint validation
 * 2. Fast lookups by CPF
 *
 * The hash is one-way (SHA-256) so the original CPF cannot be recovered from it.
 */
return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('cpf_hash', 64)->nullable()->unique()->after('cpf');
        });

        // Generate hashes for existing CPFs
        DB::table('users')
            ->whereNotNull('cpf')
            ->orderBy('id')
            ->chunk(100, function ($users): void {
                foreach ($users as $user) {
                    $cpf = $user->cpf;

                    // If encrypted, decrypt first
                    if ($cpf && str_starts_with($cpf, 'eyJ')) {
                        try {
                            $cpf = Crypt::decryptString($cpf);
                        } catch (Illuminate\Contracts\Encryption\DecryptException) {
                            continue;
                        }
                    }

                    if ($cpf) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['cpf_hash' => hash('sha256', $cpf)]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('cpf_hash');
        });
    }
};
