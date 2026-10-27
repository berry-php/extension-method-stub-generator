<?php

declare(strict_types=1);

namespace Berry\ExtensionMethodStubGenerator;

use Berry\ExtensionMethodStubGenerator\Dto\ClassExtension;
use Berry\ExtensionMethodStubGenerator\Dto\ClassStub;
use LogicException;

final class StubCollection
{
    /** @var array<string, ClassStub> */
    private array $stubs = [];

    public function add(ClassExtension $extension): void
    {
        foreach ($extension->classes as $class) {
            $name = ltrim($extension->namespace . '\\' . $class, '\\');
            $key = strtolower($name);

            $this->stubs[$key] ??= new ClassStub($extension->namespace, $class);
            $stub = $this->stubs[$key];

            foreach ($extension->uses as $use) {
                $use = ltrim($use, '\\ ');
                $stub->uses[strtolower($use)] ??= $use;
            }

            foreach ($extension->methods as $method) {
                $methodKey = strtolower($method->name);
                if (isset($stub->methods[$methodKey])) {
                    throw new LogicException(
                        "Extension method named '{$method->name}' has already been registered for class '{$name}'",
                    );
                }

                $stub->methods[$methodKey] = $method;
                $stub->methodImports[$methodKey] = $extension->uses;
            }
        }
    }

    /**
     * @return array<string, ClassStub>
     */
    public function all(): array
    {
        $stubs = $this->stubs;

        foreach ($this->stubs as $stub) {
            foreach ($stub->uses as $key => $use) {
                if ($use === '' || isset($stubs[$key])) {
                    continue;
                }

                $parts = explode('\\', $use);
                $class = array_pop($parts);
                $stubs[$key] = new ClassStub(implode('\\', $parts), $class);
            }
        }

        return $stubs;
    }

    /**
     * @return array<string, ClassStub>
     */
    public function targets(): array
    {
        $stubs = $this->stubs;
        ksort($stubs, SORT_STRING);

        return $stubs;
    }
}
