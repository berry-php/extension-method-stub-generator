<?php

declare(strict_types=1);

namespace Berry\ExtensionMethodStubGenerator\Dto;

final class ClassStub
{
    /** @var array<string, string> */
    public array $uses = [];

    /** @var array<string, Method> */
    public array $methods = [];

    /** @var array<string, list<string>> Imports in scope where each method was defined. */
    public array $methodImports = [];

    public function __construct(
        public readonly string $namespace,
        public readonly string $className,
    ) {
    }
}
