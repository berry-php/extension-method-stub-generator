<?php

declare(strict_types=1);

namespace Berry\ExtensionMethodStubGenerator\Dto;

/**
 * @phpstan-import-type MethodData from Method
 * @phpstan-type ClassExtensionData array{
 *     namespace: string,
 *     class: string|list<string>,
 *     uses?: list<string>|null,
 *     methods: list<MethodData>,
 * }
 */
final readonly class ClassExtension
{
    /**
     * @param list<string> $classes
     * @param list<string> $uses
     * @param list<Method> $methods
     */
    public function __construct(
        public string $namespace,
        public array $classes,
        public array $uses,
        public array $methods,
    ) {
    }

    /**
     * @return list<self>
     */
    public static function fromJson(string $content): array
    {
        /** @var array{extensions: list<ClassExtensionData>} $json */
        $json = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        return array_map(
            static fn(array $data): self => new self(
                namespace: $data['namespace'],
                classes: is_string($data['class']) ? [$data['class']] : $data['class'],
                uses: $data['uses'] ?? [],
                methods: array_map(Method::fromArray(...), $data['methods']),
            ),
            $json['extensions'],
        );
    }
}
