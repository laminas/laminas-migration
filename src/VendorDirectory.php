<?php

declare(strict_types=1);

namespace Laminas\Migration;

use Symfony\Component\Console\Style\SymfonyStyle;

use function file_get_contents;
use function is_dir;
use function json_decode;
use function sprintf;

class VendorDirectory extends Directory
{
    /**
     * @param string $path Project path
     * @return string Location of vendor directory
     */
    public function locate($path)
    {
        $path     = $this->normalizePath($path);
        $composer = json_decode(file_get_contents($path . '/composer.json'), true);
        return isset($composer['config']['vendor-dir'])
            ? sprintf('%s/%s', $path, (string) $composer['config']['vendor-dir'])
            : sprintf('%s/vendor', $path);
    }

    /**
     * @param string $path
     * @return void
     */
    public function remove($path, SymfonyStyle $io)
    {
        $vendorDir = $this->locate($path);
        if (! is_dir($vendorDir)) {
            return;
        }
        $io->writeln('<info>Removing configured vendor directory</info>');
        $this->rmdir($vendorDir);
    }
}
