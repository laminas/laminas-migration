<?php

declare(strict_types=1);

namespace Laminas\Migration\SpecialCase;

use function array_replace_recursive;
use function json_decode;
use function json_encode;
use function preg_match;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class ComposerJsonExtraZFSpecialCase implements SpecialCaseInterface
{
    /**
     * @inheritDoc
     */
    public function matches($filename, $content)
    {
        if (! preg_match('#\bcomposer\.json$#', $filename)) {
            return false;
        }

        $composer = json_decode($content, true);
        return isset($composer['extra']['zf']);
    }

    /**
     * @inheritDoc
     */
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
        $extraZf      = $composer['extra']['zf'];
        $extraLaminas = $composer['extra']['laminas'] ?? [];

        unset($composer['extra']['zf']);
        $composer['extra']['laminas'] = array_replace_recursive($extraZf, $extraLaminas);

        return $composer;
    }
}
