<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

use function dirname;
use function is_dir;
use function is_link;
use function mkdir;
use function preg_replace;
use function rmdir;
use function scandir;
use function sprintf;
use function str_replace;
use function substr;
use function ucfirst;
use function unlink;

class Directory
{
    /**
     * If the path provided references a directory that does not yet exist,
     * create it.
     *
     * @param string $path
     * @return void
     */
    public function createParentDirectory($path)
    {
        $directory = dirname($path);
        if (is_dir($directory)) {
            return;
        }
        mkdir($directory, 0775, $recursive = true);
    }

    /**
     * Normalize a Windows-based path to Unix-friendly format.
     *
     * @param string $path
     * @return string
     */
    public function normalizePath($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|(?<=.)/+|', '/', $path);
        if (':' === substr($path, 1, 1)) {
            $path = ucfirst($path);
        }
        return $path;
    }

    /**
     * @param string $path
     * @return void
     */
    public function rmdir($path)
    {
        foreach (scandir($path) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $test = sprintf('%s/%s', $path, $file);
            is_dir($test) && ! is_link($test)
                ? $this->rmdir($test)
                : unlink($test);
        }
        rmdir($path);
    }
}
