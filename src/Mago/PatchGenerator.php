<?php

declare(strict_types=1);

namespace Berry\ExtensionMethodStubGenerator\Mago;

use Berry\ExtensionMethodStubGenerator\Dto\ClassStub;
use RuntimeException;

final class PatchGenerator
{
    public function __construct(
        private readonly TargetResolver $resolver,
    ) {
    }

    /**
     * @param array<string, ClassStub> $targets Actual extension targets, without imported-type placeholders.
     * @return list<string> Relative paths of generated patches.
     */
    public function generate(array $targets, string $directory): array
    {
        $renderer = new PatchRenderer();
        $contents = [];
        foreach ($targets as $target) {
            $name = ($target->namespace !== '' ? $target->namespace . '\\' : '') . $target->className;
            $path = str_replace('\\', '/', $name) . '.php';
            $this->checkPath($directory, $path);
            $contents[$path] = $renderer->render($target, $this->resolver->resolve($name));
        }

        ksort($contents, SORT_STRING);

        $manifestPath = $directory . '/.manifest';
        if (is_link(dirname($directory)) || is_link($directory) || is_link($manifestPath)) {
            throw new RuntimeException("Refusing to write Mago patches through a symlink in '{$directory}'");
        }

        $previous = [];
        if (is_file($manifestPath)) {
            $manifest = file_get_contents($manifestPath);
            if ($manifest === false) {
                throw new RuntimeException("Could not read Mago manifest '{$manifestPath}'");
            }

            foreach (explode("\n", trim($manifest)) as $line) {
                if ($line === '') {
                    continue;
                }

                $entry = explode(' ', $line, 2);
                if (count($entry) !== 2 || preg_match('/\A[a-f0-9]{64}\z/', $entry[1]) !== 1) {
                    throw new RuntimeException("Invalid Mago manifest '{$manifestPath}'");
                }

                $previous[$entry[0]] = $entry[1];
            }
        }

        // Validate the entire manifest before writing or deleting anything.
        foreach ($previous as $path => $hash) {
            $this->checkPath($directory, $path);
        }

        foreach (array_keys($contents) as $path) {
            $file = $directory . '/' . $path;
            if (is_file($file) && !isset($previous[$path])) {
                throw new RuntimeException(
                    "Refusing to overwrite unowned Mago file '{$file}'; move it out of the generated directory",
                );
            }

            if (isset($previous[$path]) && is_file($file) && hash_file('sha256', $file) !== $previous[$path]) {
                throw new RuntimeException(
                    "Generated Mago file '{$file}' was modified; move it aside before regenerating",
                );
            }
        }

        if (!is_dir($directory) && !mkdir($directory, recursive: true)) {
            throw new RuntimeException("Could not create '{$directory}'");
        }

        $manifest = [];
        foreach ($contents as $path => $content) {
            $file = $directory . '/' . $path;
            if (!is_dir(dirname($file)) && !mkdir(dirname($file), recursive: true)) {
                throw new RuntimeException("Could not create directory for '{$file}'");
            }

            $this->write($file, $content);
            $manifest[$path] = hash('sha256', $content);
        }

        foreach ($previous as $path => $hash) {
            $file = $directory . '/' . $path;
            if (!isset($manifest[$path]) && is_file($file) && hash_file('sha256', $file) === $hash && !unlink($file)) {
                throw new RuntimeException("Could not remove stale Mago patch '{$file}'");
            }
        }

        $lines = [];
        foreach ($manifest as $path => $hash) {
            $lines[] = $path . ' ' . $hash;
        }

        $this->write($manifestPath, implode("\n", $lines) . "\n");

        return array_keys($contents);
    }

    private function checkPath(string $directory, string $path): void
    {
        $components = explode('/', $path);

        if (
            !str_ends_with($path, '.php')
            || str_contains($path, "\0")
            || str_contains($path, '\\')
            || count(array_intersect($components, ['', '.', '..'])) > 0
        ) {
            throw new RuntimeException("Invalid Mago patch path '{$path}'");
        }

        $current = $directory;
        foreach ($components as $component) {
            $current .= '/' . $component;
            if (is_link($current)) {
                throw new RuntimeException("Refusing to access Mago patch symlink '{$current}'");
            }
        }
    }

    private function write(string $file, string $content): void
    {
        if (file_put_contents($file, $content) === false) {
            throw new RuntimeException("Could not write '{$file}'");
        }
    }
}
