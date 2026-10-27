<?php

declare(strict_types=1);

namespace Berry\ExtensionMethodStubGenerator\Dto;

/**
 * @phpstan-import-type ArgumentData from Argument
 * @phpstan-type MethodData array{
 *     name: string,
 *     doc?: string|null,
 *     returns?: string|null,
 *     args?: list<ArgumentData>,
 * }
 */
final readonly class Method
{
    /**
     * @param list<Argument> $args
     */
    public function __construct(
        public string $name,
        public ?string $doc = null,
        public ?string $returns = null,
        public array $args = [],
    ) {
    }

    /**
     * @param MethodData $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            doc: $data['doc'] ?? null,
            returns: $data['returns'] ?? null,
            args: array_map(Argument::fromArray(...), $data['args'] ?? []),
        );
    }
}
