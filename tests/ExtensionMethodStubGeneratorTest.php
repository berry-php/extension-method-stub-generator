<?php

declare(strict_types=1);

use Berry\ExtensionMethodStubGenerator\ExtensionMethodStubGenerator;
use Composer\EventDispatcher\Event;
use Composer\Factory;
use Composer\IO\BufferIO;
use Composer\Package\Package;
use Composer\Repository\InstalledArrayRepository;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

it('generates and registers stubs from root and installed packages', function (): void {
    $cwd = getcwd();
    if ($cwd === false) {
        throw new RuntimeException('Could not determine the working directory');
    }

    $composerFile = getenv('COMPOSER');
    $temp = sys_get_temp_dir() . '/berry-test-' . bin2hex(random_bytes(8));
    mkdir($temp);

    try {
        chdir($temp);

        putenv('COMPOSER=composer.json');
        file_put_contents('composer.json', '{"name":"berry/stub-test","autoload":{"classmap":["classes/"]}}');
        mkdir('classes');

        file_put_contents(
            'classes/targets.php',
            '<?php namespace Berry\\Html; abstract class HtmlTag {} class HtmlVoidTag {}',
        );

        $io = new BufferIO();
        $composer = Factory::create($io, disablePlugins: true, disableScripts: true);
        $plugin = new ExtensionMethodStubGenerator();
        $plugin->activate($composer, $io);

        copy(__DIR__ . '/fixtures/htmx.json', 'berry-method-extensions.json');
        mkdir('vendor/berry/alpinejs', recursive: true);
        copy(__DIR__ . '/fixtures/alpinejs.json', 'vendor/berry/alpinejs/berry-method-extensions.json');

        $repository = new InstalledArrayRepository([new Package('berry/alpinejs', '1.0.0.0', '1.0.0')]);
        $composer->getRepositoryManager()->setLocalRepository($repository);
        $plugin->handleEvent(new Event(ScriptEvents::POST_INSTALL_CMD));

        foreach (['HtmlTag', 'HtmlVoidTag'] as $class) {
            expect(file_get_contents(".berry/stubs/Berry/Html/{$class}.php"))
                ->toContain('@method static hxGet(', '@method static xData(');
        }

        expect(file_get_contents('.berry/stubs/Berry/Htmx/HxTarget.php'))
            ->toContain('namespace Berry\\Htmx;', 'class HxTarget');
        expect(file_get_contents('.berry/extension.neon'))
            ->toContain(
                'stubs/Berry/Html/HtmlTag.php',
                'stubs/Berry/Html/HtmlVoidTag.php',
                'stubs/Berry/Htmx/HxTarget.php',
            );
        expect(file_get_contents('.berry/extension.neon'))->not->toContain('mago/');
        expect(file_get_contents('.berry/mago/Berry/Html/HtmlTag.php'))
            ->toContain('abstract class HtmlTag', '@method static hxGet(');
        expect(file_get_contents('.berry/mago/Berry/Html/HtmlVoidTag.php'))->toContain('class HtmlVoidTag');
        expect('.berry/mago/Berry/Htmx/HxTarget.php')->not->toBeFile();
        expect($io->getOutput())->not->toContain('generation failed');
    } finally {
        chdir($cwd);
        putenv($composerFile === false ? 'COMPOSER' : 'COMPOSER=' . $composerFile);
        (new Filesystem())->removeDirectory($temp);
    }
});
