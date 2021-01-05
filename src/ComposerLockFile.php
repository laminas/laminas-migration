<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

use Symfony\Component\Console\Style\SymfonyStyle;

use function explode;
use function file_exists;
use function file_get_contents;
use function in_array;
use function json_decode;
use function unlink;

class ComposerLockFile
{
    /**
     * @param string $path
     *
     * @return void
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
     *
     * @return void
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

        $mapper = /**
         * @return (mixed|string)[]
         *
         * @psalm-return array{0: mixed, 1: mixed|string}
         */
        static function (array $package): array {
            $name = $package['name'];
            $version = $package['version'];
            $vendor = explode('/', $name, 2)[0];

            // There are some packages from zendframework/zfcampus which received patches after migration
            // For example: https://github.com/laminas/laminas-diactoros/compare/2.2.1...2.2.1p1
            if (in_array($vendor, ['zfcampus', 'zendframework'], true)) {
                $version = sprintf('~%s.0', $version);
            }

            return [
                $name,
                $version,
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

    private function lockfile(string $path): string
    {
        return $path . '/composer.lock';
    }
}
