<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

use Laminas\ZendFrameworkBridge\Replacements;

class Helper
{
    /**
     * @param string $string
     * @return string
     */
    public static function replace($string)
    {
        static $replacements;

        /** @var Replacements $replacements */
        $replacements = $replacements ?: new Replacements([
            'Expressive\\' => 'Mezzio\\',
        ]);
        return $replacements->replace($string);
    }

    /**
     * @param string $file
     * @return false|int
     */
    public static function writeJson($file, array $data)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

        return file_put_contents($file, $content);
    }
}
