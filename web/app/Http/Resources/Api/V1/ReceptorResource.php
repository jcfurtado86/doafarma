<?php

declare(strict_types = 1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource for receptor (patient) data in medication request context.
 *
 * Contact information (email, phone) is only exposed when the request
 * status allows contact (confirmed status). This protects user privacy
 * while still allowing necessary communication for deliveries.
 *
 * @mixin \App\Models\User
 */
class ReceptorResource extends JsonResource
{
    /**
     * Whether to expose full contact details.
     */
    private bool $exposeContactDetails = false;

    /**
     * Allow contact details to be exposed.
     */
    public function withContactDetails(): self
    {
        $this->exposeContactDetails = true;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $data = [
            'id'   => $this->id,
            'name' => $this->name,
        ];

        // Only expose full contact info when explicitly allowed
        // This should be used when the medication request is confirmed
        if ($this->exposeContactDetails) {
            $data['email']        = $this->email;
            $data['phone_number'] = $this->phone_number;
        } else {
            // Masked versions for privacy
            $data['email']        = $this->maskEmail($this->email);
            $data['phone_number'] = $this->maskPhone($this->phone_number);
        }

        return $data;
    }

    /**
     * Mask email address for privacy.
     * Example: john.doe@example.com -> j*****e@e****e.com
     */
    private function maskEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return null;
        }

        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return '***@***.***';
        }

        [$local, $domain] = $parts;

        $maskedLocal = mb_strlen($local) > 2
            ? mb_substr($local, 0, 1) . str_repeat('*', mb_strlen($local) - 2) . mb_substr($local, -1)
            : str_repeat('*', mb_strlen($local));

        $domainParts  = explode('.', $domain);
        $maskedDomain = array_map(fn ($part): string => mb_strlen((string) $part) > 2
            ? mb_substr((string) $part, 0, 1) . str_repeat('*', mb_strlen((string) $part) - 2) . mb_substr((string) $part, -1)
            : str_repeat('*', mb_strlen((string) $part)), $domainParts);

        return $maskedLocal . '@' . implode('.', $maskedDomain);
    }

    /**
     * Mask phone number for privacy.
     * Example: 11987654321 -> (11) *****-4321
     */
    private function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (mb_strlen($digits) < 4) {
            return str_repeat('*', mb_strlen($digits));
        }

        $lastFour = mb_substr($digits, -4);
        $areaCode = mb_strlen($digits) >= 10 ? mb_substr($digits, 0, 2) : '**';

        return "({$areaCode}) *****-{$lastFour}";
    }
}
