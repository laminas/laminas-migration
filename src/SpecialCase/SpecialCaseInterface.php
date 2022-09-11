<?php

declare(strict_types=1);

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
