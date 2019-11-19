<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

use Symfony\Component\Console\Style\SymfonyStyle;

use function file_exists;
use function unlink;

class ComposerLockFile
{
    /**
     * @param string $path
     */
    public function remove($path, SymfonyStyle $io)
    {
        if (! file_exists($path . '/composer.lock')) {
            return;
        }
        $io->writeln('<info>Removing composer.lock</info>');
        unlink($path . '/composer.lock');
    }
}
