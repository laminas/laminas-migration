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

use const DIRECTORY_SEPARATOR;

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

If you wish to EXCLUDE files or a directory from migration, use
the --exclude (or -e) option. A common use case for that is "--exclude data"
or "--exclude data/cache". The --exclude option can be issued multiple times,
one for each directory or file you wish to exclude.

To provide a regular expression filter for explicitly MATCHING files to
rewrite, use the --filter (-f) option. The regexp provided should not contain
delimiters (the tooling use "#" as the delimiter). Files that match the regular
expression will be rewritten. This option can also be issued multiple times; if
a file matches any filter, it will be rewritten. As an example:

  laminas-migrate -f "\.(php|php\.dist|phtml|json)$" \
  > -f "Dockerfile" \
  > -f "php-entrypoint$"

The above would only rewrite files with the suffixes ".php", ".php.dist",
".phtml", and ".json", as well as Dockerfiles and scripts matching the name
"php-entrypoint".

NOTE: if a file matches BOTH a --filter AND an --exclude rule, it will be
excluded.

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
                'The path to the project/library to migrate.',
                getcwd()
            )
            ->addOption(
                'exclude',
                'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Directories or files in which to exclude rewrites.'
                . ' Always excludes the configured vendor directory and VCS directories.'
            )
            ->addOption(
                'filter',
                'f',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Regex filters describing specific files to migrate.'
                . ' Files not matching the provided pattern(s) will not be migrated.'
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
            ? sprintf('%s%s%s', $path, DIRECTORY_SEPARATOR, $composer['config']['vendor-dir'])
            : sprintf('%s%svendor', $path, DIRECTORY_SEPARATOR);
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
                if ($filter($current)) {
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
        $filters = [
            $this->createRegexFilter($input->getOption('filter')),
            $this->createExcludeFilter($input->getOption('exclude'), $path),
        ];

        return static function ($path) use ($filters) {
            foreach ($filters as $filter) {
                if (! $filter($path)) {
                    return false;
                }
            }
            return true;
        };
    }

    /**
     * @param string[] $regexFilters Regular expressions to test against
     * @return callable
     */
    private function createRegexFilter(array $regexFilters)
    {
        // If no filters are present, we always attempt to process the file.
        if (empty($regexFilters)) {
            /**
             * @return bool Always returns true.
             */
            return static function () {
                return true;
            };
        }

        /**
         * @return bool True if the file matches any filter; false otherwise.
         */
        return static function (SplFileInfo $file) use ($regexFilters) {
            // Don't handle non-file values
            if (! $file->isFile()) {
                return true;
            }

            $path = $file->getPathname();
            foreach ($regexFilters as $regex) {
                $pattern = sprintf('#%s#', $regex);
                if (preg_match($pattern, $path)) {
                    // If any filter matches, we process the file.
                    return true;
                }
            }

            // If no filter matches, we do not process the file.
            return false;
        };
    }

    /**
     * @param string[] $exclusions Paths to exclude
     * @param string $projectPath Project path
     * @return callable
     */
    private function createExcludeFilter(array $exclusions, $projectPath)
    {
        // Prepend most common exclusions
        array_unshift($exclusions, sprintf('%s%s.hg', $projectPath, DIRECTORY_SEPARATOR));
        array_unshift($exclusions, sprintf('%s%s.svn', $projectPath, DIRECTORY_SEPARATOR));
        array_unshift($exclusions, sprintf('%s%s.git', $projectPath, DIRECTORY_SEPARATOR));
        array_unshift($exclusions, $this->locateVendorDirectory($projectPath));

        // Create list of directory patterns to check against
        $directoryMatches = array_map(static function ($exclusion) {
            return sprintf('%s%s', DIRECTORY_SEPARATOR, trim($exclusion, '/\\'));
        }, $exclusions);

        // Create list of filenames to check against
        $fileMatches = array_map(static function ($exclusion) {
            return sprintf('#%s$#', preg_quote($exclusion, '|'));
        }, $exclusions);

        /**
         * @return bool
         */
        return static function (SplFileInfo $file) use ($directoryMatches, $fileMatches) {
            $path = $file->getPathname();

            // Handle directories
            if ($file->isDir()) {
                foreach ($directoryMatches as $exclusion) {
                    if (strpos($path, $exclusion) !== false) {
                        // Matched an exclusion; do not recurse
                        return false;
                    }
                }

                return true;
            }

            // Non-directory, non-files cannot be rewritten
            if (! $file->isFile()) {
                return false;
            }

            // Handle files
            foreach ($fileMatches as $exclusion) {
                if (preg_match($exclusion, $path)) {
                    // File matches exclusion pattern; do not process
                    return false;
                }
            }

            return true;
        };
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
