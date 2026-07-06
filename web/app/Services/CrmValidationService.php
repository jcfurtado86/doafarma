<?php

declare(strict_types = 1);

namespace App\Services;

use App\Enums\CrmStatus;
use App\Models\CrmRecord;
use App\Values\CrmValidationResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CrmValidationService
{
    public function validate(string $crm, string $uf): CrmValidationResult
    {
        $cached = $this->getCachedRecord($crm, $uf);

        if ($cached instanceof CrmRecord) {
            return $this->buildResultFromRecord($cached);
        }

        $apiKey = config('services.consultacrm.key');

        if (empty($apiKey)) {
            Log::warning('ConsultaCRM API key not configured — skipping CRM validation, user will be set to pending');

            return $this->unavailableResult();
        }

        try {
            return $this->queryApi($crm, $uf, $apiKey);
        } catch (\Throwable $e) {
            Log::warning('ConsultaCRM API error', [
                'crm'     => $crm,
                'uf'      => $uf,
                'message' => $e->getMessage(),
            ]);

            return $this->unavailableResult();
        }
    }

    private function getCachedRecord(string $crm, string $uf): ?CrmRecord
    {
        $record = CrmRecord::where('crm', $crm)->where('uf', $uf)->first();

        if ($record === null || $record->isExpired()) {
            return null;
        }

        return $record;
    }

    private function buildResultFromRecord(CrmRecord $record): CrmValidationResult
    {
        return new CrmValidationResult(
            status: CrmStatus::from($record->status),
            doctorName: $record->doctor_name,
            specialty: $record->specialties[0] ?? null,
            source: $record->source,
            apiAvailable: true,
        );
    }

    private function queryApi(string $crm, string $uf, string $apiKey): CrmValidationResult
    {
        $url     = config('services.consultacrm.url');
        $timeout = (int) config('services.consultacrm.timeout', 3);

        $response = Http::timeout($timeout)->get($url, [
            'tipo'    => 'crm',
            'uf'      => $uf,
            'q'       => $crm,
            'chave'   => $apiKey,
            'destino' => 'json',
        ]);

        $response->throw();

        $data = $response->json();

        return $this->parseApiResponse($data, $crm, $uf);
    }

    private function parseApiResponse(mixed $data, string $crm, string $uf): CrmValidationResult
    {
        $item = $data['item'][0] ?? null;

        if ($item === null) {
            $this->upsertRecord($crm, $uf, CrmStatus::NotFound, null, [], $data);

            return new CrmValidationResult(
                status: CrmStatus::NotFound,
                doctorName: null,
                specialty: null,
                source: 'consultacrm',
                apiAvailable: true,
            );
        }

        $statusRaw   = mb_strtolower(trim((string) ($item['situacao'] ?? '')));
        $status      = $statusRaw === '' || $statusRaw === '0' ? CrmStatus::Active : $this->mapStatus($statusRaw);
        $doctorName  = $item['nome'] ?? null;
        $specialties = $this->extractSpecialties($item);

        $this->upsertRecord($crm, $uf, $status, $doctorName, $specialties, $data);

        return new CrmValidationResult(
            status: $status,
            doctorName: $doctorName,
            specialty: $specialties[0] ?? null,
            source: 'consultacrm',
            apiAvailable: true,
        );
    }

    private function mapStatus(string $statusRaw): CrmStatus
    {
        return match (true) {
            str_contains($statusRaw, 'ativo') && ! str_contains($statusRaw, 'inativo') => CrmStatus::Active,
            str_contains($statusRaw, 'inativo')                                        => CrmStatus::Inactive,
            str_contains($statusRaw, 'cancelado')                                      => CrmStatus::Canceled,
            str_contains($statusRaw, 'suspenso')                                       => CrmStatus::Suspended,
            default                                                                    => CrmStatus::NotFound,
        };
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<int, string>
     */
    private function extractSpecialties(array $item): array
    {
        $specialties = [];

        if (! empty($item['especialidade'])) {
            $specialties[] = (string) $item['especialidade'];
        }

        return $specialties;
    }

    /**
     * @param  array<int, string>  $specialties
     */
    private function upsertRecord(
        string $crm,
        string $uf,
        CrmStatus $status,
        ?string $doctorName,
        array $specialties,
        mixed $rawResponse,
    ): void {
        $cacheDays = $status->isActive()
            ? (int) config('services.consultacrm.cache_days_valid', 30)
            : (int) config('services.consultacrm.cache_days_invalid', 7);

        CrmRecord::updateOrCreate(
            ['crm' => $crm, 'uf' => $uf],
            [
                'doctor_name'  => $doctorName,
                'status'       => $status->value,
                'specialties'  => $specialties,
                'source'       => 'consultacrm',
                'verified_at'  => now(),
                'expires_at'   => now()->addDays($cacheDays),
                'raw_response' => $rawResponse,
            ],
        );
    }

    private function unavailableResult(): CrmValidationResult
    {
        return new CrmValidationResult(
            status: CrmStatus::Unknown,
            doctorName: null,
            specialty: null,
            source: 'unavailable',
            apiAvailable: false,
        );
    }
}
