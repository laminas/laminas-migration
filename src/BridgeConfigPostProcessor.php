<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

use Symfony\Component\Console\Style\SymfonyStyle;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function preg_match;
use function sprintf;
use function str_replace;

class BridgeConfigPostProcessor
{
    /**
     * @param string $path
     * @param bool $disableConfigProcessorInjection
     * @return void
     */
    public function inject($path, $disableConfigProcessorInjection, SymfonyStyle $io)
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
