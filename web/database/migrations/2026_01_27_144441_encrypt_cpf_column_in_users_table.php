<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to encrypt CPF data for LGPD compliance.
 *
 * This migration:
 * 1. Changes CPF column to TEXT to accommodate encrypted data
 * 2. Encrypts existing CPF values
 * 3. Removes unique constraint (encrypted values can't be indexed)
 *
 * IMPORTANT: After this migration, CPF searches must decrypt all values.
 * Consider creating a separate hashed_cpf column for lookups if needed.
 */
return new class () extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Remove unique constraint and change column type
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['cpf']);
            $table->text('cpf')->nullable()->change();
        });

        // Step 2: Encrypt existing CPF values
        DB::table('users')
            ->whereNotNull('cpf')
            ->orderBy('id')
            ->chunk(100, function ($users): void {
                foreach ($users as $user) {
                    // Only encrypt if not already encrypted (starts with eyJ which is base64 JSON)
                    if ($user->cpf && ! str_starts_with((string) $user->cpf, 'eyJ')) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update(['cpf' => Crypt::encryptString($user->cpf)]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Decrypt existing CPF values
        DB::table('users')
            ->whereNotNull('cpf')
            ->orderBy('id')
            ->chunk(100, function ($users): void {
                foreach ($users as $user) {
                    if ($user->cpf && str_starts_with((string) $user->cpf, 'eyJ')) {
                        try {
                            $decrypted = Crypt::decryptString($user->cpf);
                            DB::table('users')
                                ->where('id', $user->id)
                                ->update(['cpf' => $decrypted]);
                        } catch (Illuminate\Contracts\Encryption\DecryptException) {
                            // If decryption fails, leave as is
                        }
                    }
                }
            });

        // Step 2: Change column back to varchar and add unique constraint
        Schema::table('users', function (Blueprint $table): void {
            $table->string('cpf', 11)->nullable()->change();
            $table->unique('cpf');
        });
    }
};
