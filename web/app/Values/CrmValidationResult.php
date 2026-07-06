<?php

declare(strict_types = 1);

namespace App\Values;

use App\Enums\CrmStatus;

final readonly class CrmValidationResult
{
    public function __construct(
        public CrmStatus $status,
        public ?string $doctorName,
        public ?string $specialty,
        public string $source,
        public bool $apiAvailable,
    ) {
    }

    public function isApproved(): bool
    {
        return $this->status->isActive();
    }

    public function isRejected(): bool
    {
        return $this->status->isRejectable();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }
}
