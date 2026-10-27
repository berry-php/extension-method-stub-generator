<?php

declare(strict_types=1);

namespace Berry\ExtensionMethodStubGenerator\Mago;

use Composer\Autoload\ClassLoader;
use PhpToken;
use RuntimeException;

final class TargetResolver
{
    public function __construct(
        private readonly ClassLoader $loader,
    ) {
    }

    public function resolve(string $name): string
    {
        $file = $this->loader->findFile($name);
        if ($file === false) {
            throw new RuntimeException(
                "Cannot resolve Mago target '{$name}'; declare it in Composer's PSR-4, PSR-0 or classmap autoload configuration",
            );
        }

        $source = file_get_contents($file);
        if ($source === false) {
            throw new RuntimeException("Cannot read declaration of Mago target '{$name}' from '{$file}'");
        }

        $tokens = array_values(array_filter(
            PhpToken::tokenize($source),
            static fn(PhpToken $item): bool => !$item->isIgnorable(),
        ));
        $namespace = '';
        $depth = 0;
        $namespaceDepth = null;
        foreach ($tokens as $index => $token) {
            if ($depth === 0 && $token->id === T_NAMESPACE) {
                $namespace = '';
                for ($next = $index + 1; isset($tokens[$next]) && !$tokens[$next]->is([';', '{']); $next++) {
                    $namespace .= $tokens[$next]->text;
                }

                $namespaceDepth = isset($tokens[$next]) && $tokens[$next]->text === '{' ? $depth + 1 : null;
            }

            if ($token->is(['{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES])) {
                $depth++;
            } elseif ($token->is('}')) {
                $depth--;
                if ($namespaceDepth !== null && $depth < $namespaceDepth) {
                    $namespace = '';
                    $namespaceDepth = null;
                }
            }

            if (
                !$token->is([T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])
                || !isset($tokens[$index + 1])
                || $tokens[$index + 1]->id !== T_STRING
            ) {
                continue;
            }

            $declaredName = ltrim($namespace . '\\' . $tokens[$index + 1]->text, '\\');
            if (strcasecmp($declaredName, $name) !== 0) {
                continue;
            }

            if ($token->id === T_ENUM) {
                throw new RuntimeException(
                    "Unsupported Mago target '{$name}'; only classes, interfaces and traits are supported (not enums)",
                );
            }

            $modifiers = [];
            for (
                $previous = $index - 1;
                $previous >= 0 && $tokens[$previous]->is([T_FINAL, T_ABSTRACT, T_READONLY]);
                $previous--
            ) {
                array_unshift($modifiers, strtolower($tokens[$previous]->text));
            }

            return implode(' ', [...$modifiers, strtolower($token->text)]);
        }

        throw new RuntimeException(
            "No declaration for Mago target '{$name}' found in '{$file}'; class aliases and runtime declarations are not supported",
        );
    }
}
