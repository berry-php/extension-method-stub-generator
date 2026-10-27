<?php

declare(strict_types=1);

namespace Berry\ExtensionMethodStubGenerator\Dto;

/**
 * @phpstan-type ArgumentData array{name: string, type?: string|null, defaultValue?: string|null}
 */
final readonly class Argument
{
    public function __construct(
        public string $name,
        public ?string $type = null,
        public ?string $defaultValue = null,
    ) {
    }

    /**
     * @param ArgumentData $data
     */
    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }
}
