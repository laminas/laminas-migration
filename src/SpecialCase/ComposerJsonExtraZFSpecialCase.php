<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration\SpecialCase;

class ComposerJsonExtraZFSpecialCase implements SpecialCaseInterface
{
    public function matches($filename, $content)
    {
        if (! preg_match('#\bcomposer\.json$#', $filename)) {
            return false;
        }

        $composer = json_decode($content, true);
        return isset($composer['extra']['zf']);
    }

    public function replace($content)
    {
        $composer = json_decode($content, true);
        $composer = $this->updateComposer($composer);

        return json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @return array The updated Composer array.
     */
    private function updateComposer(array $composer)
    {
        $extraZf = $composer['extra']['zf'];
        $extraLaminas = isset($composer['extra']['laminas']) ? $composer['extra']['laminas'] : [];

        unset($composer['extra']['zf']);
        $composer['extra']['laminas'] = array_replace_recursive($extraZf, $extraLaminas);

        return $composer;
    }
}
