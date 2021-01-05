<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration\Command;

use InvalidArgumentException;
use Laminas\Migration\BridgeConfigPostProcessor;
use Laminas\Migration\BridgeModule;
use Laminas\Migration\ComposerLockFile;
use Laminas\Migration\DependencyPlugin;
use Laminas\Migration\FileFilter;
use Laminas\Migration\MigrateProject;
use Laminas\Migration\SpecialCase\ComposerJsonExtraZFSpecialCase;
use Laminas\Migration\SpecialCase\ComposerJsonZendFrameworkPackageSpecialCase;
use Laminas\Migration\VendorDirectory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class MigrateCommand extends Command
{
    public const HELP = <<< EOH
Migrate a project or library to target Laminas, Mezzio, and/or Laminas API
Tools packages.

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

- Injecting the `Laminas\ZendFrameworkBridge` module into MVC and API
  applications. This module provides configuration post processing to replace,
  at runtime, references to known Zend Framework configuration keys and
  dependencies with the Laminas equivalents.

- Injecting the `Laminas\ZendFrameworkBridge\ConfigPostProcessor` class as a
  `Laminas\ConfigAggregator\ConfigAggregator` post processor into Mezzio
  applications. This class provides configuration post processing to replace,
  at runtime, references to known Zend Framework configuration keys and
  dependencies with the Laminas equivalents.

If you wish to prevent injection of the laminas/laminas-dependency-plugin in
your application, use the --no-plugin option.

If you wish to prevent injection of either the laminas-zendframework-bridge
Module or ConfigPostProcessor into your application, use the
--no-config-processor option.

If you want to keep your currently locked package versions, use the flag --keep-locked-versions option.
NOTE: By using a diff-tool, you can easily restore the old version constraints after you executed `composer install`.
To update your lockfile, you can just use `composer update --lock` 

EOH;

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('migrate')
            ->setDescription('Migrate a project or third-party library to target Laminas, API Tools, or Mezzio')
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
            )
            ->addOption(
                'keep-locked-versions',
                null,
                InputOption::VALUE_NONE,
                'Parse existing composer.lock (if available) and pass locked versions to composer.json'
            )
            ->addOption(
                'yes',
                'y',
                InputOption::VALUE_NONE,
                'Indicate that you acknowledge that the tooling will remove your composer.lock and vendor directory'
            );
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! $this->approveVendorDeletion($input, $output)) {
            return 1;
        }

        $io   = new SymfonyStyle($input, $output);
        $path = $input->getArgument('path');

        $io->title(sprintf('Migrating project at path "%s" to Laminas', $path));

        if (! $this->validatePath($path, $io)) {
            return 1;
        }

        $path = realpath($path);
        assert(is_string($path), new InvalidArgumentException('Unable to determine realpath from provided path'));

        if ($input->getOption('keep-locked-versions')) {
            $this->synchronizeComposerJsonWithComposerLock($path, $io);
        }

        $this->removeComposerLock($path, $io);
        $this->removeVendorDirectory($path, $io);
        $this->injectDependencyPlugin($path, $input->getOption('no-plugin'), $io);
        $this->migrateProjectFiles($path, $this->createFilter($input), $io);

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
     *
     * @return void
     */
    private function removeComposerLock($path, SymfonyStyle $io): void
    {
        $composerLockFile = new ComposerLockFile();
        $composerLockFile->remove($path, $io);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    private function removeVendorDirectory($path, SymfonyStyle $io): void
    {
        $vendorDirectory = new VendorDirectory();
        $vendorDirectory->remove($path, $io);
    }

    /**
     * @param string $path
     * @param null|bool $noPluginOption
     *
     * @return void
     */
    private function injectDependencyPlugin($path, $noPluginOption, SymfonyStyle $io): void
    {
        $dependencyPlugin = new DependencyPlugin();
        $dependencyPlugin->inject($path, $noPluginOption, $io);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    private function migrateProjectFiles($path, callable $filter, SymfonyStyle $io): void
    {
        $migration = new MigrateProject([
            new ComposerJsonExtraZFSpecialCase(),
            new ComposerJsonZendFrameworkPackageSpecialCase(),
        ]);
        $migration($path, $filter, $io);
    }

    /**
     * @param string $path
     * @return callable
     */
    private function createFilter(InputInterface $input)
    {
        $filter = $input->getOption('filter');
        assert(is_array($filter), new InvalidArgumentException('Invalid --filter option provided; expected array'));

        $exclude = $input->getOption('exclude');
        assert(is_array($exclude), new InvalidArgumentException('Invalid --exclude option provided; expected array'));

        return new FileFilter($filter, $exclude);
    }

    /**
     * @param string $path
     * @param bool $disableConfigProcessorInjection
     *
     * @return void
     */
    private function injectBridgeModule($path, $disableConfigProcessorInjection, SymfonyStyle $io): void
    {
        $bridgeModule = new BridgeModule();
        $bridgeModule->inject($path, $disableConfigProcessorInjection, $io);
    }

    /**
     * @param string $path
     * @param bool $disableConfigProcessorInjection
     *
     * @return void
     */
    private function injectBridgeConfigPostProcessor($path, $disableConfigProcessorInjection, SymfonyStyle $io): void
    {
        $bridgeConfigPostProcessor = new BridgeConfigPostProcessor();
        $bridgeConfigPostProcessor->inject($path, $disableConfigProcessorInjection, $io);
    }

    /**
     * @param string $path
     *
     * @return void
     */
    private function synchronizeComposerJsonWithComposerLock($path, SymfonyStyle $io): void
    {
        $lockFile = new ComposerLockFile();
        $lockFile->moveLockedVersionsToComposerJson($path, $io);
    }

    /**
     * @return bool
     */
    private function approveVendorDeletion(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('yes')) {
            return true;
        }

        if (! $input->isInteractive()) {
            $output->writeln(
                '<error>You must pass the --yes flag indicating you acknowledge this command'
                . ' will remove your composer.lock file and vendor directory</error>'
            );
            return false;
        }

        $question = new ConfirmationQuestion(
            '<question>This command REMOVES your composer.lock file and vendor directory;'
            . ' do you wish to continue?</question>'
        );
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        return $helper->ask($input, $output, $question);
    }
}
