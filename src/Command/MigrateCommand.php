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
    protected function configure()
    {
        $this->setName('migrate')
            ->setDescription('Migrate a project or third-party library to target Laminas/Expressive/Apigility')
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
        $this->migrateProjectFiles($path, $input->getOption('exclude'), $io);
        $this->injectBridgeModule($path, $io);
        $this->injectBridgeConfigPostProcessor($path, $io);

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
     * @param string[] $exclusions Directories to exclude
     */
    private function migrateProjectFiles($path, array $exclusions, SymfonyStyle $io)
    {
        $io->writeln('<info>Performing migration replacements</info>');
        foreach ($this->findProjectFiles($path, $exclusions) as $file) {
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
     * @param string[] $excludePaths
     * @return RecursiveIteratorIterator|SplFileInfo[]
     */
    private function findProjectFiles($path, array $excludePaths)
    {
        $exclude = $this->createExclusionChecker($path, $excludePaths);

        $dir = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS
        );

        $files = new RecursiveCallbackFilterIterator(
            $dir,
            static function (SplFileInfo $current, $key, $iterator) use ($exclude) {
                if ($iterator->hasChildren()) {
                    return true;
                }

                if ($current->isFile() && ! $exclude($current->getPathname())) {
                    return true;
                }

                return false;
            }
        );

        return new RecursiveIteratorIterator($files);
    }

    /**
     * @param string $path
     * @param string[] $excludePaths
     * @return callable
     */
    private function createExclusionChecker($path, array $excludePaths)
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
        return static function ($path) use ($excludePaths) {
            foreach ($excludePaths as $excludePath) {
                if (strpos($path, $excludePath) !== false) {
                    return true;
                }
            }
            return false;
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
     * param string $path
     */
    private function injectBridgeModule($path, SymfonyStyle $io)
    {
        $modulesConfig = sprintf('%s/config/modules.config.php', $path);
        if (! file_exists($modulesConfig)) {
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
     */
    private function injectBridgeConfigPostProcessor($path, SymfonyStyle $io)
    {
        $configFile = sprintf('%s/config/config.php', $path);
        if (! file_exists($configFile)) {
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
