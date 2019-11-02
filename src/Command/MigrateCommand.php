<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration\Command;

use Laminas\Migration\Helper;
use Laminas\ZendFrameworkBridge\Replacements;
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
    /** @var Replacements */
    private $replacements;

    public function __construct($name = null)
    {
        $this->replacements = new Replacements();
        parent::__construct($name);
    }

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

        if (! $this->validatePath($path, $output)) {
            return 1;
        }

        $path = realpath($path);

        $this->removeComposerLock($path);
        $this->removeVendorDirectory($path);

        if (! $input->getOption('no-plugin')) {
            $this->injectDependencyPlugin($path);
        }

        foreach ($this->findProjectFiles($path, $input->getOption('exclude')) as $file) {
            $this->performReplacements($file->getRealPath(), $path);
        }

        return 0;
    }

    /**
     * @param string $path
     * @return bool
     */
    private function validatePath($path, OutputInterface $output)
    {
        if (file_exists($path . '/composer.json')) {
            return true;
        }

        $output->writeln(sprintf(
            '<error>Cannot find composer.json file in %s path</error>',
            $path
        ));
        return false;
    }

    /**
     * @param string $path
     * @return void
     */
    private function removeComposerLock($path)
    {
        if (! file_exists($path . '/composer.lock')) {
            return;
        }
        unlink($path . '/composer.lock');
    }

    private function removeVendorDirectory($path)
    {
        $vendorDir = $this->locateVendorDirectory($path);
        if (! is_dir($vendorDir)) {
            return;
        }
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
     * @return void
     */
    private function injectDependencyPlugin($path)
    {
        $json = json_decode(file_get_contents($path . '/composer.json'), true);
        $json['require']['laminas/laminas-dependency-plugin'] = '^0.1.2';
        Helper::writeJson($path . '/composer.json', $json);
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
        $content = $this->replacements->replace($content);
        file_put_contents($file, $content);

        // Only rewrite the portion under the project root path.
        $newName = sprintf('%s/%s', $path, $this->replacements->replace(substr($file, strlen($path) + 1)));
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
}
