<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration\Command;

use InvalidArgumentException;
use Laminas\Migration\Helper;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NestedDepsCommand extends Command
{
    public const DESCRIPTION = <<<'EOH'
Update project to require Laminas-variants of nested Zend Framework package dependencies.
EOH;

    public const HELP = <<<'EOH'
Sometimes, third-party packages will depend on Zend Framework packages.
In such cases, Composer will go ahead and install them if replacements
are not already required by the project.

This command will look for installed Zend Framework packages, and then
call the Composer binary to require the Laminas equivalents, using the
same constraints as previously used. As Laminas packages are marked as
replacements of their ZF equivalents, this will remove the ZF packages
as well.
EOH;

    /**
     * @return void
     */
    public function configure()
    {
        $this->setName('nested-deps')
             ->setDescription(self::DESCRIPTION)
             ->setHelp(self::HELP)
             ->addArgument(
                 'path',
                 InputArgument::OPTIONAL,
                 'The path to the project/library to migrate',
                 getcwd()
             )
             ->addOption(
                 'composer',
                 'c',
                 InputOption::VALUE_REQUIRED,
                 'The path to the Composer binary, if not on your $PATH',
                 'composer'
             );
    }

    /**
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $composer = $input->getOption('composer');
        assert(is_string($composer), new InvalidArgumentException('Invalid composer option provided'));

        $path = $input->getArgument('path');
        assert(is_string($path), new InvalidArgumentException('Invalid path argument provided'));
        if ($path !== getcwd()) {
            chdir($path);
        }

        $output->writeln('<info>Checking for Zend Framework packages in project</info>');

        $command = sprintf('%s show -f json', $composer);
        $results = [];
        $status = 0;

        exec($command, $results, $status);

        if ($status !== 0) {
            $output->writeln(
                '<error>Unable to execute "composer show"; are you sure the path is correct?</error>'
            );
            return 1;
        }

        $data = json_decode(trim(implode("\n", $results)));
        if (! isset($data->installed)) {
            return 1;
        }

        $packages = [];

        foreach ($data->installed as $package) {
            if (! preg_match('#^(zfcampus|zendframework)/#', $package->name)) {
                continue;
            }

            $packages[] = $this->preparePackageInfo($package, $composer, $output);
        }

        if (! $packages) {
            $output->writeln('<info>No Zend Framework packages detected; nothing to do!</info>');
            return 0;
        }

        $createPackageSpec = static function (array $package): string {
            return sprintf('"%s:~%s"', Helper::replace($package['name']), ltrim($package['version'], 'v'));
        };

        // Require root packages
        $success = $this->requirePackages(
            $output,
            $composer,
            array_map(
                $createPackageSpec,
                array_filter($packages, static function (array $package): bool {
                    return ! array_key_exists('dev', $package) || ! $package['dev'];
                })
            ),
            $forDev = false
        );

        // Require dev packages
        $success = $this->requirePackages(
            $output,
            $composer,
            array_map(
                $createPackageSpec,
                array_filter($packages, static function (array $package): bool {
                    return array_key_exists('dev', $package) && $package['dev'];
                })
            ),
            $forDev = true
        ) && $success;

        return $success ? 0 : 1;
    }

    /**
     * @param string $composer
     * @return array {
     *     @var string $name
     *     @var string $version
     *     @var bool $dev
     * }
     */
    private function preparePackageInfo(stdClass $package, $composer, OutputInterface $output)
    {
        return [
            'name' => $package->name,
            'version' => $package->version,
            'dev' => $this->isDevPackage($package->name, $composer, $output),
        ];
    }

    /**
     * @param string $packageName
     * @param string $composer
     * @return bool
     */
    private function isDevPackage($packageName, $composer, OutputInterface $output)
    {
        $command = sprintf('%s why -r %s', $composer, $packageName);
        $results = [];
        $status = 0;

        exec($command, $results, $status);

        if ($status !== 0) {
            $output->writeln(sprintf(
                '<error>Error executing "%s"</error>',
                $command
            ));
            $output->writeln('<Info>Output:</error>');
            $output->writeln(implode(PHP_EOL, $results));
            return false;
        }

        $root = array_shift($results);
        if ($root && is_string($root) && strpos($root, '(for development)') !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param string $composer
     * @param string[] $packages
     * @param bool $forDev
     * @return bool
     */
    private function requirePackages(OutputInterface $output, $composer, array $packages, $forDev)
    {
        if (! $packages) {
            // Nothing to do!
            return true;
        }

        $output->writeln(sprintf(
            '<info>Preparing to require the following packages%s:</info>',
            $forDev ? ' (for development)' : ''
        ));
        $output->writeln(implode("\n", array_map(function (string $package) {
            return sprintf('- %s', trim($package, '"'));
        }, $packages)));

        $command = sprintf(
            '%s require %s%s',
            $composer,
            $forDev ? '--dev ' : '',
            implode(' ', $packages)
        );
        passthru($command, $status);

        if ($status !== 0) {
            $output->writeln(sprintf(
                '<error>Error executing "%s"; please check the above logs for details</error>',
                $command
            ));
            return false;
        }

        return true;
    }
}
