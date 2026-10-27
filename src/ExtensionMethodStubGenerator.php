<?php

declare(strict_types=1);

namespace Berry\ExtensionMethodStubGenerator;

use Berry\ExtensionMethodStubGenerator\Dto\ClassExtension;
use Berry\ExtensionMethodStubGenerator\Dto\ClassStub;
use Berry\ExtensionMethodStubGenerator\Mago\PatchGenerator;
use Berry\ExtensionMethodStubGenerator\Mago\TargetResolver;
use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Composer;
use Composer\Factory;
use Exception;
use Override;
use RuntimeException;

final class ExtensionMethodStubGenerator implements PluginInterface, EventSubscriberInterface
{
    private const string EXT_FILENAME = '/berry-method-extensions.json';
    private const string STUBS_DIR = '/.berry/stubs';

    private Composer $composer;
    private IOInterface $io;

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'handleEvent',
            ScriptEvents::POST_UPDATE_CMD => 'handleEvent',
        ];
    }

    #[Override]
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    #[Override]
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    #[Override]
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public function handleEvent(Event $_event): void
    {
        $this->io->write('<info>berry: Scanning all package roots...</info>');

        $stubs = new StubCollection();

        foreach ($this->gatherBerryExtensionPackages() as $packageName => $packagePath) {
            try {
                $file = $packagePath . self::EXT_FILENAME;

                $this->io->write("<info>berry: Installing method extension stubs from '{$packageName}'...</info>");

                $content = file_get_contents($file);
                if ($content === false) {
                    throw new RuntimeException("Could not read '{$file}'");
                }

                foreach (ClassExtension::fromJson($content) as $extension) {
                    $stubs->add($extension);
                }
            } catch (Exception $ex) {
                $this->io->error(
                    "berry: Error when trying to parse '{$packagePath}' from '{$packageName}': {$ex->getMessage()}",
                );
                continue;
            }
        }

        $this->generateStubs($stubs->all());
        $this->generateMagoPatches($stubs);
    }

    private function generateMagoPatches(StubCollection $stubs): void
    {
        $this->io->write('<info>berry: Generating Mago patches...</info>');

        try {
            $root = $this->composer->getPackage();
            $autoload = $this->composer->getAutoloadGenerator();

            $packages = $autoload->buildPackageMap(
                $this->composer->getInstallationManager(),
                $root,
                $this->composer->getRepositoryManager()->getLocalRepository()->getPackages(),
            );

            $loader = $autoload->createLoader($autoload->parseAutoloads($packages, $root));
            $rootPath = realpath(dirname(Factory::getComposerFile()));

            if ($rootPath === false) {
                throw new RuntimeException('Could not resolve the project root');
            }

            $files = (new PatchGenerator(new TargetResolver($loader)))->generate(
                $stubs->targets(),
                $rootPath . '/.berry/mago',
            );

            foreach ($files as $file) {
                $this->io->write("<info>berry: Wrote .berry/mago/{$file}</info>");
            }
        } catch (Exception $exception) {
            $this->io->error('berry: Mago patch generation failed: ' . $exception->getMessage());
        }
    }

    /**
     * @return array<string, string>
     */
    private function gatherBerryExtensionPackages(): array
    {
        $packages = [];

        $im = $this->composer->getInstallationManager();
        $repo = $this->composer->getRepositoryManager()->getLocalRepository();

        // handle installed packages
        foreach ($repo->getPackages() as $package) {
            $path = $im->getInstallPath($package);

            if ($path === null || !is_dir($path)) {
                continue;
            }

            if ($this->containsBerryExtensionMethods($path)) {
                $this->io->write("<info>berry: Found: {$package->getName()} implementing extension methods</info>");
                $packages[$package->getName()] = $path;
            }
        }

        // handle current package
        $rootPackage = $this->composer->getPackage();
        $rootPath = realpath(dirname(Factory::getComposerFile()));

        if ($rootPath !== false && $this->containsBerryExtensionMethods($rootPath)) {
            $packages[$rootPackage->getName()] = $rootPath;
        }

        return $packages;
    }

    private function containsBerryExtensionMethods(string $path): bool
    {
        $path = realpath($path);

        if ($path === false) {
            return false;
        }

        return is_file($path . self::EXT_FILENAME);
    }

    /**
     * @param array<string, ClassStub> $stubs
     */
    private function generateStubs(array $stubs): void
    {
        $rootPath = realpath(dirname(Factory::getComposerFile()));
        if ($rootPath === false) {
            throw new RuntimeException('Could not resolve the project root');
        }

        $stubsRoot = $rootPath . self::STUBS_DIR;
        if (!is_dir($stubsRoot) && !mkdir($stubsRoot, recursive: true)) {
            throw new RuntimeException("Could not create '{$stubsRoot}'");
        }

        $files = [];
        $renderer = new StubRenderer();

        foreach ($stubs as $stub) {
            $name = ltrim($stub->namespace . '\\' . $stub->className, '\\');
            $files[] = $this->writeStub($stubsRoot, $name, $renderer->render($stub));
        }

        $this->writePhpstanExtensionFile($files, $rootPath);
        $this->io->write('<info>berry: Stub generation completed!</info>');
    }

    private function writeStub(string $stubsRoot, string $name, string $content): string
    {
        $relativePath = str_replace('\\', '/', $name) . '.php';
        $filePath = $stubsRoot . '/' . $relativePath;
        $dir = dirname($filePath);

        if (!is_dir($dir) && !mkdir($dir, recursive: true)) {
            throw new RuntimeException("Could not create '{$dir}'");
        }

        if (file_put_contents($filePath, $content) === false) {
            throw new RuntimeException("Could not write '{$filePath}'");
        }

        $this->io->write("<info>berry: Wrote {$filePath}</info>");

        return 'stubs/' . $relativePath;
    }

    /**
     * @param list<string> $files
     */
    private function writePhpstanExtensionFile(array $files, string $rootPath): void
    {
        $files = array_unique($files);
        sort($files, SORT_STRING);

        $neonPath = $rootPath . '/.berry/extension.neon';

        $content = "parameters:\n    stubFiles:";

        if (count($files) === 0) {
            $content .= " []\n";
        } else {
            $content .= "\n";

            foreach ($files as $f) {
                $content .= "        - {$f}\n";
            }
        }

        if (file_put_contents($neonPath, $content) === false) {
            throw new RuntimeException("Could not write '{$neonPath}'");
        }

        $this->io->write("<info>berry: Wrote {$neonPath}</info>");
    }
}
