<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration\Command;

use Laminas\Migration\Helper;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCommand extends Command
{
    const HELP = <<< EOH
Migrate a project or library to target Laminas, Expressive, and/or Apigility
packages.

<info>Basic Usage</info>
<info>-----------</info>

In most cases, the command can be run without any arguments, in which case it
will migrate the project in the current directory, rewriting any files that
contain references to Zend Framework artifacts to instead reference the Laminas
equivalents.

If you wish to specify a path other than the current working directory, use the
--path option.

<info>Excluding Files</info>
<info>---------------</info>

If you wish to exclude all files under a given directory from migration, use
the --exclude (or -e) option. A common use case for that is "--exclude data"
or "--exclude data/cache". The --exclude option can be issued multiple times,
one for each directory you wish to exclude.

To exclude individual files, use the --exclude-file (-x) option. This option
can also be issued multiple times.

To provide a regular expression filter for matching files to rewrite, use the
--filter (-f) option. The regexp provided should not contain delimiters (the
tooling use "#" as the delimiter). Files that match the regular expression will
be rewritten. This option can also be issued multiple times; if a file matches
any filter, it will be rewritten. As an example:

  laminas-migrate -f "\.(php|php\.dist|phtml|json)$" \
  > -f "Dockerfile" \
  > -f "php-entrypoint$"

The above would only rewrite files with the suffixes ".php", ".php.dist",
".phtml", and ".json", as well as Dockerfiles and scripts matching the name
"php-entrypoint".

<info>Injections</info>
<info>----------</info>

The tooling provides three potential new injections into your code base:

- Injecting the laminas/laminas-dependency-plugin Composer plugin as a
  dependency. This plugin intercepts requests to install Zend Framework
  packages, and substitutes the Laminas equivalents.

- Injecting the `Laminas\ZendFrameworkBridge` module into MVC and Apigility
  applications. This module provides configuration post processing to replace,
  at runtime, references to known Zend Framework configuration keys and
  dependencies with the Laminas equivalents.

- Injecting the `Laminas\ZendFrameworkBridge\ConfigPostProcessor` class as a
  `Laminas\ConfigAggregator\ConfigAggregator` post processor into Expressive
  applications. This class provides configuration post processing to replace,
  at runtime, references to known Zend Framework configuration keys and
  dependencies with the Laminas equivalents.

If you wish to prevent injection of the laminas/laminas-dependency-plugin in
your application, use the --no-plugin option.

If you wish to prevent injection of either the laminas-zendframework-bridge
Module or ConfigPostProcessor into your application, use the
--no-config-processor option.

EOH;

    protected function configure()
    {
        $this->setName('migrate')
            ->setDescription('Migrate a project or third-party library to target Laminas/Expressive/Apigility')
            ->setHelp(self::HELP)
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'The path to the project/library to migrate',
                getcwd()
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Directories in which to exclude rewrites. Always excludes .git and the configured vendor directories.'
            )
            ->addOption(
                'exclude-file',
                'x',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Individual files in which to exclude rewrites.'
            )
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Filters'
            )
            ->addOption(
                'no-config-processor',
                null,
                InputOption::VALUE_NONE,
                'Do not install either the laminas/laminas-zendframework-bridge'
                . ' Module or ConfigPostProcessor in your application'
            )
            ->addOption(
                'no-plugin',
                null,
                InputOption::VALUE_NONE,
                'Do not install laminas/laminas-dependency-plugin'
            );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');

        $io->title(sprintf('Migrating project at path "%s" to Laminas', $path));

        if (! $this->validatePath($path, $io)) {
            return 1;
        }

        $path = realpath($path);

        $this->removeComposerLock($path, $io);
        $this->removeVendorDirectory($path, $io);
        $this->injectDependencyPlugin($path, $input->getOption('no-plugin'), $io);
        $this->migrateProjectFiles($path, $this->createFilter($path, $input), $io);

        $disableConfigProcessorInjection = $input->getOption('no-config-processor');
        $this->injectBridgeModule($path, $disableConfigProcessorInjection, $io);
        $this->injectBridgeConfigPostProcessor($path, $disableConfigProcessorInjection, $io);

        $io->success('Migration complete!');
        $io->text([
            '<info>Next steps:</info>',
            '- Perform a diff to verify the changes made.',
            '- Run "composer install".',
            '- Run any tests (unit tests, integration tests, end-to-end tests, etc.).'
        ]);

        return 0;
    }

    /**
     * @param string $path
     * @return bool
     */
    private function validatePath($path, SymfonyStyle $io)
    {
        if (file_exists($path . '/composer.json')) {
            return true;
        }

        $io->error(sprintf(
            'Cannot find composer.json file in %s path',
            $path
        ));
        return false;
    }

    /**
     * @param string $path
     */
    private function removeComposerLock($path, SymfonyStyle $io)
    {
        if (! file_exists($path . '/composer.lock')) {
            return;
        }
        $io->writeln('<info>Removing composer.lock</info>');
        unlink($path . '/composer.lock');
    }

    /**
     * @param string $path
     */
    private function removeVendorDirectory($path, SymfonyStyle $io)
    {
        $vendorDir = $this->locateVendorDirectory($path);
        if (! is_dir($vendorDir)) {
            return;
        }
        $io->writeln('<info>Removing configured vendor directory</info>');
        $this->removeDirectory($vendorDir);
    }

    private function removeDirectory($path)
    {
        foreach (scandir($path) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $test = sprintf('%s/%s', $path, $file);
            is_dir($test) && ! is_link($test)
                ? $this->removeDirectory($test)
                : unlink($test);
        }
        rmdir($path);
    }

    /**
     * @param string $path Project path
     * @return string Location of vendor directory
     */
    private function locateVendorDirectory($path)
    {
        $composer  = json_decode(file_get_contents($path . '/composer.json'), true);
        return isset($composer['config']['vendor-dir'])
            ? realpath($path) . '/' . $composer['config']['vendor-dir'] . '/'
            : realpath($path) . '/vendor/';
    }

    /**
     * @param string $path
     * @param null|bool $noPluginOption
     */
    private function injectDependencyPlugin($path, $noPluginOption, SymfonyStyle $io)
    {
        if ($noPluginOption) {
            return;
        }

        $io->writeln('<info>Injecting laminas-dependency-plugin into composer.json</info>');
        $json = json_decode(file_get_contents($path . '/composer.json'), true);
        $json['require']['laminas/laminas-dependency-plugin'] = '^0.1.2';
        Helper::writeJson($path . '/composer.json', $json);
    }

    /**
     * @param string $path
     */
    private function migrateProjectFiles($path, callable $filter, SymfonyStyle $io)
    {
        $io->writeln('<info>Performing migration replacements</info>');
        foreach ($this->findProjectFiles($path, $filter) as $file) {
            $this->performReplacements($file->getRealPath(), $path);
        }
    }

    /**
     * Perform replacements in $file, and rename $file if necessary
     *
     * @param string $file File being examined and updated
     * @param string $path Project root path
     * @return void
     */
    private function performReplacements($file, $path)
    {
        $content = file_get_contents($file);
        $content = Helper::replace($content);
        file_put_contents($file, $content);

        // Only rewrite the portion under the project root path.
        $newName = sprintf('%s/%s', $path, Helper::replace(substr($file, strlen($path) + 1)));
        if ($newName !== $file) {
            $this->createNewDirectory($newName);
            rename($file, $newName);
        }
    }

    /**
     * @param string $path
     * @return RecursiveIteratorIterator|SplFileInfo[]
     */
    private function findProjectFiles($path, callable $filter)
    {
        $dir = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS
        );

        $files = new RecursiveCallbackFilterIterator(
            $dir,
            static function (SplFileInfo $current, $key, $iterator) use ($filter) {
                if ($iterator->hasChildren()) {
                    return true;
                }

                if ($current->isFile() && ! $filter($current->getPathname())) {
                    return true;
                }

                return false;
            }
        );

        return new RecursiveIteratorIterator($files);
    }

    /**
     * @param string $path
     * @return callable
     */
    private function createFilter($path, InputInterface $input)
    {
        $filters = [];
        $filters = $this->createFileFilter($input->getOption('filter'), $path, $filters);
        $filters = $this->createDirectoryExclusionChecker($input->getOption('exclude'), $path, $filters);
        $filters = $this->createFileExclusionChecker($input->getOption('exclude-file'), $path, $filters);

        return static function ($path) use ($filters) {
            foreach ($filters as $filter) {
                if ($filter($path)) {
                    return true;
                }
            }
            return false;
        };
    }

    /**
     * @param string[] $fileFilters Regular expressions to test against
     * @param string $path
     * @param callable[] $filters
     * @return array The $filters array with the new file filter
     *     appended, if created.
     */
    private function createFileFilter(array $fileFilters, $path, array $filters)
    {
        if (empty($filters)) {
            return $filters;
        }

        /**
         * @param string $path
         * @return bool
         */
        $filters[] = static function ($path) use ($fileFilters) {
            foreach ($fileFilters as $filter) {
                $pattern = sprintf('#%s#', $filter);
                if (preg_match($pattern, $path)) {
                    // If any filter matches, we process the file
                    return false;
                }
            }
            // No filter matched
            return true;
        };

        return $filters;
    }

    /**
     * @param string[] $excludePaths
     * @param string $path
     * @param callable[] $filters
     * @return array The $filters array with the new exclusion filter
     *     appended. This one is always added, as it ensures exclusion of the
     *     vendor and .git directories.
     */
    private function createDirectoryExclusionChecker(array $excludePaths, $path, array $filters)
    {
        /**
         * @param string $path
         * @return string
         */
        $normalization = static function ($path) {
            return sprintf('/%s/', trim($path, '/\\'));
        };

        // Normalize paths to ensure they are searched as directory segments
        $excludePaths = array_map($normalization, $excludePaths);

        // Prepend most common exclusions
        array_unshift($excludePaths, $this->locateVendorDirectory($path));
        array_unshift($excludePaths, '/.git/');

        /**
         * @param string $path
         * @return bool
         */
        $filters[] = static function ($path) use ($excludePaths) {
            foreach ($excludePaths as $excludePath) {
                if (strpos($path, $excludePath) !== false) {
                    return true;
                }
            }
            return false;
        };

        return $filters;
    }

    /**
     * @param string[] $excludePaths
     * @param string $path
     * @param callable[] $filters
     * @return array The $filters array with the new exclusion filter
     *     appended, if created.
     */
    private function createFileExclusionChecker(array $excludePaths, $path, array $filters)
    {
        if (empty($filters)) {
            return $filters;
        }

        /**
         * @param string $path
         * @return bool
         */
        $filters[] = static function ($path) use ($excludePaths) {
            foreach ($excludePaths as $excludePath) {
                $pattern = sprintf('|%s$|', preg_quote($excludePath, '|'));
                if (preg_match($pattern, $path)) {
                    return true;
                }
            }
            return false;
        };

        return $filters;
    }

    /**
     * If the path provided references a directory that does not yet exist,
     * create it.
     *
     * @param string $path
     * @return void
     */
    private function createNewDirectory($path)
    {
        $directory = dirname($path);
        if (is_dir($directory)) {
            return;
        }
        mkdir($directory, 0775, $recursive = true);
    }

    /**
     * @param string $path
     * @param bool $disableConfigProcessorInjection
     */
    private function injectBridgeModule($path, $disableConfigProcessorInjection, SymfonyStyle $io)
    {
        $modulesConfig = sprintf('%s/config/modules.config.php', $path);
        if (! file_exists($modulesConfig)) {
            return;
        }

        if ($disableConfigProcessorInjection) {
            $io->writeln('<info>Skipping injection of bridge module by request (--no-config-processor)</info>');
            return;
        }

        $io->writeln(sprintf(
            '<info>Injecting Laminas\ZendFrameworkBridge module into %s</info>',
            $modulesConfig
        ));

        $contents = file_get_contents($modulesConfig);
        if (! preg_match('/(?<prelude>return\s+(array\(|\[))(?<space>\s+)/s', $contents, $matches)) {
            $io->error('- File is not in expected format; aborting injection');
            $io->text(
                'You will need to manually add an entry for "Laminas\ZendFrameworkBridge"'
                . ' in your module configuration.'
            );
            return;
        }

        $search = $matches['prelude'] . $matches['space'];
        $replacement = sprintf(
            '%s%s\'Laminas\ZendFrameworkBridge\',%s',
            $matches['prelude'],
            $matches['space'],
            $matches['space']
        );
        $newContents = str_replace($search, $replacement, $contents);

        file_put_contents($modulesConfig, $newContents);
    }

    /**
     * @param string $path
     * @param bool $disableConfigProcessorInjection
     */
    private function injectBridgeConfigPostProcessor($path, $disableConfigProcessorInjection, SymfonyStyle $io)
    {
        $configFile = sprintf('%s/config/config.php', $path);
        if (! file_exists($configFile)) {
            return;
        }

        if ($disableConfigProcessorInjection) {
            $io->writeln(
                '<info>Skipping injection of bridge configuration post processor'
                . ' by request (--no-config-processor)</info>'
            );
            return;
        }

        $io->writeln(sprintf(
            '<info>Injecting Laminas\ZendFrameworkBridge\ConfigPostProcessor into %s</info>',
            $configFile
        ));

        $contents = file_get_contents($configFile);
        if (! preg_match('/(?<prelude>\$cacheConfig\[\'config_cache_path\'\])\);/s', $contents, $matches)) {
            $io->error('- File is not in expected format; aborting injection');
            $io->text(
                'You will need to manually add the "Laminas\ZendFrameworkBridge\ConfigPostProcessor"'
                . ' in your ConfigAggregator initialization.'
            );
            return;
        }

        $search = $matches['prelude'];
        $replacement = sprintf(
            '%s, [\Laminas\ZendFrameworkBridge\ConfigPostProcessor::class]',
            $matches['prelude']
        );
        $newContents = str_replace($search, $replacement, $contents);

        file_put_contents($configFile, $newContents);
    }
}
