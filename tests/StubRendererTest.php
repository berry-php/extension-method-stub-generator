<?php

declare(strict_types=1);

use Berry\ExtensionMethodStubGenerator\Dto\ClassExtension;
use Berry\ExtensionMethodStubGenerator\Dto\ClassStub;
use Berry\ExtensionMethodStubGenerator\StubRenderer;

it('preserves method signatures and documentation from Berry descriptors', function (
    string $fixture,
    array $expected,
): void {
    $content = file_get_contents(__DIR__ . "/fixtures/{$fixture}.json");

    if ($content === false) {
        throw new RuntimeException("Could not read fixture '{$fixture}'");
    }

    $extension = ClassExtension::fromJson($content)[0];
    $stub = new ClassStub($extension->namespace, $extension->classes[0]);

    foreach ($extension->uses as $use) {
        $stub->uses[$use] = $use;
    }

    foreach ($extension->methods as $method) {
        $stub->methods[$method->name] = $method;
    }

    expect((new StubRenderer())->render($stub))->toContain(...$expected);
})->with([
    'HTMX' => [
        'htmx',
        [
            'use Berry\\Htmx\\HxTarget;',
            '@method static hxGet(string $url) Issues a GET request to the specified URL',
            '@method static hxTarget(HxTarget|string $val, bool $inherited = false, bool $append = false)',
            '@method static hxOn(string $event, ?string $js = null)',
        ],
    ],
    'Alpine' => [
        'alpinejs',
        [
            "@method static xData(array<mixed>|string|\\Stringable \$data = '{}')",
            '@method static xTransition(string|null $suffix = null, string|\\Stringable|null $expression = null)',
            '@method static xCloak() Hides uninitialized Alpine content',
        ],
    ],
]);
