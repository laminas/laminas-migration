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
use function json_decode;
use function unlink;

class ComposerLockFile
{
    /**
     * @param string $path
     */
    public function remove($path, SymfonyStyle $io)
    {
        $lockfile = $this->lockfile($path);
        if (! file_exists($lockfile)) {
            return;
        }
        $io->writeln('<info>Removing composer.lock</info>');
        unlink($lockfile);
    }

    /**
     * @param string $path
     */
    public function moveLockedVersionsToComposerJson($path, SymfonyStyle $io)
    {
        $composerLock = $this->lockfile($path);
        if (!file_exists($composerLock)) {
            return;
        }

        $composerJson = $path . '/composer.json';
        if (!file_exists($composerJson)) {
            return;
        }

        $io->writeln('<info>Moving locked package versions to composer.json</info>');

        $composerLockData = json_decode(file_get_contents($composerLock), true);
        $composerJsonData = json_decode(file_get_contents($composerJson), true);

        $mapper = static function (array $package) {
            return [
                $package['name'],
                $package['version'],
            ];
        };

        foreach ($composerLockData['packages'] as $package) {
            list($packageName, $version) = $mapper($package);
            $composerJsonData['require'][$packageName] = $version;
        }

        foreach ($composerLockData['packages-dev'] as $package) {
            list($packageName, $version) = $mapper($package);
            $composerJsonData['require-dev'][$packageName] = $version;
        }

        Helper::writeJson($composerJson, $composerJsonData);
    }

    private function lockfile($path)
    {
        return $path . '/composer.lock';
    }
}
