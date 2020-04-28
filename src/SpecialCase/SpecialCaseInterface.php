<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration\SpecialCase;

interface SpecialCaseInterface
{
    /**
     * @param string $filename
     * @param string $content
     * @return bool Whether or not the given file can be addressed as a special case.
     */
    public function matches($filename, $content);

    /**
     * @param string $content Content of the file
     * @return string Modified file contents
     */
    public function replace($content);
}
