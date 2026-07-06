<?php

declare(strict_types = 1);

namespace App\Values;

use App\Enums\TokenAbility;

/**
 * Encapsulates the format Sanctum token names follow in this app.
 *
 * Sanctum stores a single `name` string per token. We embed both the
 * device name and the token ability (access/refresh) in it, so this
 * value object owns the format and keeps callers from re-implementing
 * the montagem/parsing logic.
 */
final readonly class TokenName
{
    private const SEPARATOR = ':';

    public function __construct(
        public string $device,
        public TokenAbility $ability,
    ) {
    }

    public static function forNewToken(string $device, TokenAbility $ability): self
    {
        return new self($device, $ability);
    }

    public static function fromStoredName(string $name): self
    {
        $parts   = explode(self::SEPARATOR, $name);
        $ability = TokenAbility::from(end($parts));
        $device  = implode(self::SEPARATOR, array_slice($parts, 0, -1));

        return new self($device, $ability);
    }

    public function toString(): string
    {
        return $this->device . self::SEPARATOR . $this->ability->value;
    }
}
