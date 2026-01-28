<?php declare(strict_types=1);

namespace Berry\ExtensionMethodStubGenerator;

use Composer\EventDispatcher\Event;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Composer;
use Composer\Factory;
use Exception;
use LogicException;

/**
 * @phpstan-type Argument array{
 *     name: string,
 *     type?: string|null,
 *     defaultValue?: string|null,
 * }
 * @phpstan-type Method array{
 *     name: string,
 *     doc?: string|null,
 *     returns?: string|null,
 *     args?: list<Argument>,
 * }
 * @phpstan-type ClassExtension array{
 *     namespace: string,
 *     class: string|list<string>,
 *     uses?: list<string>|null,
 *     methods: list<Method>,
 * }
 * @phpstan-type ExtensionStubFile array{extensions: ClassExtension[]}
 */
final class ExtensionMethodStubGenerator implements PluginInterface, EventSubscriberInterface
{
    private const string EXT_FILENAME = '/berry-method-extensions.json';
    private const string STUBS_DIR = '/.berry/stubs';

    private ?Composer $composer = null;
    private ?IOInterface $io = null;

    public static function getSubscribedEvents()
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'handleEvent',
            ScriptEvents::POST_UPDATE_CMD => 'handleEvent',
        ];
    }

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io) {}

    public function uninstall(Composer $composer, IOInterface $io) {}

    public function handleEvent(Event $event): void
    {
        assert($this->composer !== null);
        assert($this->io !== null);

        $this->io->write('<info>berry: Scanning all package roots...</info>');

        /** @var array<string, array<string, mixed>> */
        $namespaces = [];

        foreach ($this->gatherBerryExtensionPackages() as $packageName => $packagePath) {
            try {
                $file = $packagePath . self::EXT_FILENAME;
                assert(is_file($file));

                $this->io->write("<info>berry: Installing method extension stubs from '$packageName'...</info>");

                $content = file_get_contents($file);
                assert($content !== false);

                /** @var ExtensionStubFile */
                $json = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

                foreach ($json['extensions'] as $extension) {
                    $parts = explode('\\', $extension['namespace']);

                    /** @var array<string, mixed> */
                    $curr = &$namespaces;

                    // go to the current namespace
                    foreach ($parts as $part) {
                        $curr[$part] ??= [];

                        /** @var array<string, mixed> */
                        $curr = &$curr[$part];
                    }

                    $classes = $extension['class'];

                    if (is_string($classes)) {
                        $classes = [$classes];
                    }

                    /** @var array<string, ClassExtension> $curr */
                    $curr = $curr;

                    foreach ($classes as $class) {
                        $curr[$class] ??= [];
                        $curr[$class]['namespace'] = $extension['namespace'];
                        $curr[$class]['class'] = $class;
                        $curr[$class]['uses'] ??= [];
                        $curr[$class]['uses'] = array_unique(array_merge($curr[$class]['uses'], $extension['uses'] ?? []));

                        $curr[$class]['methods'] ??= [];

                        foreach ($extension['methods'] as $method) {
                            // if already exists...
                            if (array_find($curr[$class]['methods'], fn(
                                /** @var Method $m */
                                array $m
                            ) => $m['name'] === $method['name']) !== null) {
                                throw new LogicException("Extension method named '{$method['name']}' has already been registered for class '{$extension['namespace']}\\{$class}'");
                            }

                            $curr[$class]['methods'][] = $method;
                        }
                    }
                }
            } catch (Exception $ex) {
                $this->io->error("berry: Error when trying to parse '$packagePath' from '$packageName': {$ex->getMessage()}");
                continue;
            }
        }

        $this->generateStubs($namespaces);
    }

    /**
     * @return array<string, string>
     */
    private function gatherBerryExtensionPackages(): array
    {
        assert($this->composer !== null);
        assert($this->io !== null);

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
     * @param array<string, array<string, mixed>> $tree
     */
    private function generateStubs(array $tree): void
    {
        $rootPath = realpath(dirname(Factory::getComposerFile()));
        assert($rootPath !== false);

        $stubsRoot = $rootPath . self::STUBS_DIR;

        if (!is_dir($stubsRoot)) {
            mkdir($stubsRoot, recursive: true);
        }

        $this->generateStub($tree, $stubsRoot);

        $this->io?->write('<info>berry: Stub generation completed!</info>');
    }

    /**
     * @param array<string, array<string, mixed>> $tree
     */
    private function generateStub(array $tree, string $currentPath): void
    {
        foreach ($tree as $namespacePart => $node) {
            // if its a leaf (class)
            if (isset($node['class']) && is_string($node['class'])) {
                /** @var ClassExtension */
                $node = $node;

                $class = $node['class'];
                assert(is_string($class));

                $filePath = $currentPath . DIRECTORY_SEPARATOR . $class . '.php';

                $content = $this->generateClass($node);

                file_put_contents($filePath, $content);

                $this->io?->write("<info>berry: Wrote $filePath</info>");

                continue;
            }

            // its another namespace branch
            $dir = $currentPath . DIRECTORY_SEPARATOR . $namespacePart;

            if (!is_dir($dir)) {
                mkdir($dir, recursive: true);
            }

            /** @var array<string, array<string, mixed>> */
            $node = $node;

            $this->generateStub($node, $dir);
        }
    }

    /**
     * @param ClassExtension $class
     */
    private function generateClass(array $class): string
    {
        $namespace = $class['namespace'];
        $className = $class['class'];
        assert(is_string($className));

        $usesString = '';

        $uses = $class['uses'] ?? [];
        sort($uses);

        if (count($uses) > 0) {
            $usesString .= "\n";
        }

        foreach ($uses as $use) {
            $usesString .= "use {$use};\n";
        }

        $docString = '';

        $methods = $class['methods'];
        usort($methods, fn(array $a, array $b) => strcmp($a['name'], $b['name']));

        foreach ($methods as $method) {
            $docString .= "\n * @method ";

            if (isset($method['returns'])) {
                $docString .= $method['returns'] . ' ';
            }

            $docString .= $method['name'];
            $docString .= '(';

            foreach ($method['args'] ?? [] as $index => $arg) {
                if (isset($arg['type'])) {
                    $docString .= $arg['type'] . ' ';
                }

                $docString .= "\${$arg['name']}";

                if (isset($arg['defaultValue'])) {
                    $docString .= ' = ' . $arg['defaultValue'];
                }

                if ($index !== array_key_last($method['args'])) {
                    $docString .= ', ';
                }
            }

            $docString .= ') ';

            if (isset($method['doc'])) {
                $docString .= $method['doc'];
            }
        }

        return trim(<<<PHP
            <?php declare(strict_types=1);

            /** This file was automatically generated by berry/extension-method-stub-generator, please don't edit it */

            namespace {$namespace};
            {$usesString}
            /**{$docString}
             */
            class {$className}
            {
                // stub
            }
            PHP);
    }
}
