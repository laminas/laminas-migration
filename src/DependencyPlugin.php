<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

use Symfony\Component\Console\Style\SymfonyStyle;

use function file_get_contents;
use function json_decode;

class DependencyPlugin
{
    /**
     * @param string $path
     * @param null|bool $noPluginOption
     * @return void
     */
    public function inject($path, $noPluginOption, SymfonyStyle $io)
    {
        if ($noPluginOption) {
            return;
        }

        $io->writeln('<info>Injecting laminas-dependency-plugin into composer.json</info>');
        $json = json_decode(file_get_contents($path . '/composer.json'), true);
        $json['require']['laminas/laminas-dependency-plugin'] = '^2.1';
        Helper::writeJson($path . '/composer.json', $json);
    }
}
