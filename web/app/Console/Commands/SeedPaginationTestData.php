<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Doctor;
use App\Models\Drug;
use App\Models\MedicationAppointment;
use App\Models\MedicationOffering;
use App\Models\MedicationRequest;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Comando temporário para testar paginação.
 * Cria dados suficientes para ver infinite scroll em ação.
 *
 * USO: php artisan test:seed-pagination
 * REVERTER: php artisan test:seed-pagination --rollback
 */
class SeedPaginationTestData extends Command
{
    #[\Override]
    protected $signature = 'test:seed-pagination {--rollback : Remove os dados de teste}';

    #[\Override]
    protected $description = 'Cria dados de teste para verificar paginação (temporário)';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        return $this->seed();
    }

    private function seed(): int
    {
        $this->info('Criando dados de teste para paginação...');

        // Buscar usuários existentes
        $doctor   = Doctor::first();
        $receptor = User::where('role', 'receptor')->where('status', 'approved')->first();

        if (! $doctor || ! $receptor) {
            $this->error('Precisa ter pelo menos 1 médico e 1 receptor aprovados no banco.');

            return 1;
        }

        $address = Address::where('user_id', $doctor->user_id)->first();

        if (! $address) {
            $this->error('O médico precisa ter pelo menos 1 endereço cadastrado.');

            return 1;
        }

        DB::beginTransaction();

        try {
            // Criar 60 offerings (para testar 2 páginas com limite 50)
            $this->info('Criando 60 medication offerings...');
            $drugs = Drug::inRandomOrder()->limit(10)->get();

            if ($drugs->isEmpty()) {
                $this->error('Precisa ter drogas no banco. Rode: php artisan db:seed --class=DrugSeeder');

                return 1;
            }

            for ($i = 0; $i < 60; $i++) {
                MedicationOffering::create([
                    'doctor_id'  => $doctor->id,
                    'drug_id'    => $drugs->random()->id,
                    'quantity'   => random_int(1, 10),
                    'lot_number' => 'TEST' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'expires_at' => now()->addMonths(random_int(1, 12)),
                    'notes'      => '[TESTE PAGINAÇÃO] Oferta de teste #' . $i,
                    'status'     => 'available',
                ]);
            }

            // Criar 20 appointments completed (para testar 2 páginas com limite 15)
            $this->info('Criando 20 medication appointments completed...');

            for ($i = 0; $i < 20; $i++) {
                // Criar offering específico para este appointment
                $offering = MedicationOffering::create([
                    'doctor_id'  => $doctor->id,
                    'drug_id'    => $drugs->random()->id,
                    'quantity'   => 0, // Já foi doado
                    'lot_number' => 'HIST' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'expires_at' => now()->addMonths(random_int(1, 6)),
                    'notes'      => '[TESTE PAGINAÇÃO] Histórico #' . $i,
                    'status'     => 'completed',
                ]);

                // Criar request
                $request = MedicationRequest::create([
                    'medication_offering_id' => $offering->id,
                    'receptor_id'            => $receptor->id,
                    'quantity_requested'     => 1,
                    'status'                 => 'confirmed',
                ]);

                // Criar appointment completed
                MedicationAppointment::create([
                    'medication_request_id' => $request->id,
                    'address_id'            => $address->id,
                    'scheduled_date'        => now()->subDays(random_int(1, 30)),
                    'scheduled_time'        => sprintf('%02d:00', random_int(8, 18)),
                    'status'                => 'completed',
                    'receptor_confirmed'    => true,
                    'doctor_confirmed'      => true,
                    'updated_at'            => now()->subDays(random_int(1, 30)), // Variação para testar ordenação
                ]);
            }

            DB::commit();

            $this->newLine();
            $this->info('✅ Dados de teste criados com sucesso!');
            $this->table(
                ['Tipo', 'Quantidade', 'Limite por página', 'Páginas esperadas'],
                [
                    ['Offerings disponíveis', '60+', '50', '2+'],
                    ['Appointments completed', '20+', '15', '2+'],
                ]
            );

            $this->newLine();
            $this->warn('⚠️  Para remover os dados de teste após testar:');
            $this->line('   php artisan test:seed-pagination --rollback');

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Erro ao criar dados: ' . $e->getMessage());

            return 1;
        }
    }

    private function rollback(): int
    {
        $this->info('Removendo dados de teste...');

        DB::beginTransaction();

        try {
            // Remover appointments de teste (via notes do offering)
            $testAppointments = MedicationAppointment::whereHas('medicationRequest.medicationOffering', function ($q): void {
                $q->where('notes', 'LIKE', '[TESTE PAGINAÇÃO]%');
            })->delete();

            // Remover requests de teste
            $testRequests = MedicationRequest::whereHas('medicationOffering', function ($q): void {
                $q->where('notes', 'LIKE', '[TESTE PAGINAÇÃO]%');
            })->delete();

            // Remover offerings de teste
            $testOfferings = MedicationOffering::where('notes', 'LIKE', '[TESTE PAGINAÇÃO]%')->delete();

            DB::commit();

            $this->info("✅ Removidos: {$testOfferings} offerings, {$testRequests} requests, {$testAppointments} appointments");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Erro ao remover dados: ' . $e->getMessage());

            return 1;
        }
    }
}
