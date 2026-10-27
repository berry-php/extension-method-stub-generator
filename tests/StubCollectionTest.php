<?php

declare(strict_types=1);

use Berry\ExtensionMethodStubGenerator\Dto\ClassExtension;
use Berry\ExtensionMethodStubGenerator\Dto\Method;
use Berry\ExtensionMethodStubGenerator\StubCollection;

it('combines HTMX and Alpine contributions for every target class and includes imported types', function (): void {
    $collection = new StubCollection();

    foreach (['htmx', 'alpinejs'] as $name) {
        $content = file_get_contents(__DIR__ . "/fixtures/{$name}.json");

        if ($content === false) {
            throw new RuntimeException("Could not read fixture '{$name}'");
        }

        foreach (ClassExtension::fromJson($content) as $extension) {
            $collection->add($extension);
        }
    }

    $stubs = $collection->all();

    foreach (['berry\\html\\htmltag', 'berry\\html\\htmlvoidtag'] as $name) {
        expect(array_map(static fn(Method $method): string => $method->name, $stubs[$name]->methods))
            ->toContain('hxGet', 'hxTarget', 'hxOn', 'xData', 'xTransition', 'xCloak');
    }

    expect($stubs)->toHaveKeys(['berry\\htmx\\hxswap', 'berry\\htmx\\hxtarget']);
});
