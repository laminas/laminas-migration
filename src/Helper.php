<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

class Helper
{
    /**
     * @param string $string
     * @return string
     */
    public static function replace($string)
    {
        return strtr($string, self::replacements());
    }

    /**
     * @param string $file
     * @return false|int
     */
    public static function writeJson($file, array $data)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        return file_put_contents($file, $content);
    }

    /**
     * @return string[]
     */
    private static function replacements()
    {
        static $replacements;

        if (! is_array($replacements)) {
            $replacements = include __DIR__ . '/../config/replacements.php';
        }

        return $replacements;
    }
}
