<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration\Command;

use Laminas\ZendFrameworkBridge\RewriteRules;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            );
    }

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

        $replacements = RewriteRules::namespaceRewrite();
        // Do not rewrite:
        $replacements['ZF\Console'] = 'ZF\Console';
        $replacements['zfcampus/zf-console'] = 'zfcampus/zf-console';
        $replacements['Zend\Version'] = 'Zend\Version';
        $replacements['zendframework/zend-version'] = 'zendframework/zend-version';
        $replacements['ZendPdf'] = 'ZendPdf';
        $replacements['zendframework/zendpdf'] = 'zendframework/zendpdf';
        $replacements['zf-commons'] = 'zf-commons';
        $replacements['api-skeletons/zf-'] = 'api-skeletons/zf-';
        $replacements['phpro/zf-'] = 'phpro/zf-';

        // Packages rewrite rules:
        $replacements['zenddiagnostics'] = 'laminas-diagnostics';
        $replacements['zendoauth'] = 'laminas-oauth';
        $replacements['zendservice-apple-apns'] = 'laminas-apple-apns';
        $replacements['zendservice-google-gcm'] = 'laminas-google-gcm';
        $replacements['zendservice-recaptcha'] = 'laminas-recaptcha';
        $replacements['zendservice-twitter'] = 'laminas-twitter';
        $replacements['zendxml'] = 'laminas-xml';
        $replacements['zendservice-twitter'] = 'laminas-twitter';
        $replacements['zendframework/zend-problem-details'] = 'expressive/expressive-problem-details';
        $replacements['zfcampus/zf-composer-autoloading'] = 'laminas/laminas-composer-autoloading';
        $replacements['zfcampus/zf-deploy'] = 'laminas/laminas-deploy';
        $replacements['zfcampus/zf-development-mode'] = 'laminas/laminas-development-mode';

        // Additional rules - Config/Names
        $replacements['Zend'] = 'Laminas';
        $replacements['zendframework'] = 'laminas';
        $replacements['zend-expressive'] = 'expressive';
        $replacements['zend_expressive'] = 'expressive';
        $replacements['zend'] = 'laminas';
        $replacements['zf-apigility'] = 'apigility';
        $replacements['zf_apigility'] = 'apigility';
        $replacements['zf-'] = 'apigility-';
        $replacements['zf_'] = 'apigility_';
        $replacements['zfcampus'] = 'apigility';

        foreach ($this->findProjectFiles($path) as $file) {
            $content = file_get_contents($file);
            $content = strtr($content, $replacements);
            file_put_contents($file, $content);

            $newName = strtr($file, $replacements);
            if ($newName !== $file) {
                rename($file, $newName);
            }
        }
    }

    private function findProjectFiles($path)
    {
        $composer = json_decode(file_get_contents($path . '/composer.json'), true);

        $vendorDir = isset($composer['config']['vendor-dir'])
            ? $path . '/' . $composer['config']['vendor-dir'] . '/'
            : $path . '/vendor/';
        $vendorDir = realpath($vendorDir);

        $dir = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS
        );

        $files = new RecursiveCallbackFilterIterator(
            $dir,
            function (SplFileInfo $current, $key, $iterator) use ($vendorDir) {
                if ($iterator->hasChildren()) {
                    return true;
                }

                if ($current->isFile() && strpos($current->getPathname(), $vendorDir) === false) {
                    return true;
                }

                return false;
            }
        );

        return new RecursiveIteratorIterator($files);
    }
}
