<?php

declare(strict_types=1);

use Berry\ExtensionMethodStubGenerator\Dto\ClassExtension;
use Berry\ExtensionMethodStubGenerator\Mago\PatchRenderer;
use Berry\ExtensionMethodStubGenerator\StubCollection;

it('renders annotation-only Mago patches from Berry descriptors', function (string $fixture, array $expected): void {
    $content = file_get_contents(__DIR__ . "/fixtures/{$fixture}.json");
    if ($content === false) {
        throw new RuntimeException("Could not read fixture '{$fixture}'");
    }

    $collection = new StubCollection();
    foreach (ClassExtension::fromJson($content) as $extension) {
        $collection->add($extension);
    }

    $target = $collection->targets()['berry\\html\\htmltag'];
    $patch = (new PatchRenderer())->render($target, 'abstract class');

    expect($patch)->toContain('namespace Berry\\Html;', 'abstract class HtmlTag', ...$expected);
    expect($patch)->not->toContain('function ', 'extends ', 'implements ');
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
