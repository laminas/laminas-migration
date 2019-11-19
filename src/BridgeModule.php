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

class BridgeModule
{
    /**
     * @param string $path
     * @param bool $disableConfigProcessorInjection
     * @return void
     */
    public function inject($path, $disableConfigProcessorInjection, SymfonyStyle $io)
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
}
