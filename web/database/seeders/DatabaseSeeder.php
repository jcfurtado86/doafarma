<?php

declare(strict_types = 1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run: php artisan migrate:fresh --seed
     *
     * This will create:
     * - Admin: admin@doafarma.com.br / admin123 (painel Filament)
     * - Doctor: doctor@example.com / password (Dr. João Santos, CRM 123456/SP)
     * - Receptor: receptor@example.com / password (Maria Silva)
     * - 10 real medications (Losartana, Metformina, Omeprazol, etc.)
     * - 5 medication offerings from the doctor
     * - Sample requests and appointments for testing
     * - 3 completed appointments for medication history (receptor)
     */
    public function run(): void
    {
        $this->call([
            // 1. Create drugs (medications) first
            DrugSeeder::class,

            // 2. Create users with profiles and addresses
            AdminSeeder::class,
            DoctorUserSeeder::class,
            ReceptorUserSeeder::class,

            // 3. Create medication offerings (requires doctor and drugs)
            MedicationOfferingSeeder::class,

            // 4. Create requests and appointments (requires all above)
            MedicationRequestSeeder::class,

            // 5. Create completed appointments for medication history
            MedicationHistorySeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('===== DADOS DE ACESSO =====');
        $this->command->info('');
        $this->command->info('Admin (painel /admin):');
        $this->command->info('  Email: admin@doafarma.com.br');
        $this->command->info('  Senha: admin123');
        $this->command->info('');
        $this->command->info('Médico:');
        $this->command->info('  Email: doctor@example.com');
        $this->command->info('  Senha: password');
        $this->command->info('  CRM: 123456/SP');
        $this->command->info('');
        $this->command->info('Receptor (Paciente):');
        $this->command->info('  Email: receptor@example.com');
        $this->command->info('  Senha: password');
        $this->command->info('');
        $this->command->info('===========================');
    }
}
