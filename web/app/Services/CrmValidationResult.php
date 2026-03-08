<?php

declare(strict_types = 1);

namespace App\Services;

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

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->apiAvailable && $this->status->isActive();
    }

    /**
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->apiAvailable && $this->status->isRejectable();
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return ! $this->apiAvailable || $this->status === CrmStatus::NotFound;
    }
}
