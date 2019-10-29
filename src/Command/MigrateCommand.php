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

        foreach ($this->findProjectFiles($path) as $file) {
            $content = file_get_contents($file);
            $content = Helper::replace($content);
            file_put_contents($file, $content);

            $newName = Helper::replace($file);
            if ($newName !== $file) {
                rename($file, $newName);
            }
        }

        return 0;
    }

    /**
     * @param string $path
     * @return RecursiveIteratorIterator
     */
    private function findProjectFiles($path)
    {
        $exclude = $this->createExclusionChecker($path);

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
     * @return callable
     */
    private function createExclusionChecker($path)
    {
        $exclusions = [];
        $composer = json_decode(file_get_contents($path . '/composer.json'), true);
        $vendorDir = isset($composer['config']['vendor-dir'])
            ? $path . '/' . $composer['config']['vendor-dir'] . '/'
            : $path . '/vendor/';

        $exclusions[] = realpath($vendorDir);
        $exclusions[] = '/.git/';

        /**
         * @param string $path
         * @return bool
         */
        return static function ($path) use ($exclusions) {
            foreach ($exclusions as $exclude) {
                if (strpos($path, $exclude) !== false) {
                    return true;
                }
            }
            return false;
        };
    }
}
