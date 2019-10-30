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
        $path = $input->getArgument('path');

        if (! file_exists($path . '/composer.json')) {
            $output->writeln(sprintf(
                '<error>Cannot find composer.json file in %s path</error>',
                $path
            ));

            return 1;
        }

        if (file_exists($path . '/composer.lock')) {
            unlink($path . '/composer.lock');
        }

        $noPlugin = $input->getOption('no-plugin');
        if (! $noPlugin) {
            $json = json_decode(file_get_contents($path . '/composer.json'), true);
            $json['require']['laminas/laminas-dependency-plugin'] = '^0.1.2';
            Helper::writeJson($path . '/composer.json', $json);
        }

        foreach ($this->findProjectFiles($path, $input->getOption('exclude')) as $file) {
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

        return 0;
    }

    /**
     * @param string $path
     * @param string[] $excludePaths
     * @return RecursiveIteratorIterator
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

        $composer = json_decode(file_get_contents($path . '/composer.json'), true);
        $vendorDir = isset($composer['config']['vendor-dir'])
            ? $path . '/' . $composer['config']['vendor-dir'] . '/'
            : $path . '/vendor/';

        // Prepend most common exclusions
        array_unshift($excludePaths, realpath($vendorDir));
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
}
