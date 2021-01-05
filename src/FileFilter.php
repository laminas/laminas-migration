<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

use SplFileInfo;

use function array_map;
use function array_unshift;
use function preg_match;
use function preg_quote;
use function sprintf;
use function strpos;
use function trim;

class FileFilter
{
    /**
     * @var string[]
     */
    private $directoryExclusions = [];

    /** @var string[] */
    private $exclusions = [];

    /**
     * @var string[]
     */
    private $fileExclusions = [];

    /** @var string[] */
    private $regexes = [];

    /**
     * @param string[] $regexes
     * @param string[] $exclusions
     * @return self
     */
    public function __construct(array $regexes, array $exclusions)
    {
        $this->regexes    = $regexes;
        $this->exclusions = $exclusions;

        $this->prependCommonExclusions();
        $this->prepareExclusionPatterns();
    }

    /**
     * @return bool True if the file should be processed; false otherwise
     */
    public function __invoke(SplFileInfo $file)
    {
        if (! $this->applyRegexFilter($file)) {
            return false;
        }

        if (! $this->applyExclusionFilter($file)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool True if we should process the file, false otherwise.
     */
    public function applyRegexFilter(SplFileInfo $file)
    {
        if (empty($this->regexes)) {
            return true;
        }

        if (! $file->isFile()) {
            return true;
        }

        $path = $file->getPathname();
        foreach ($this->regexes as $regex) {
            // Pattern is not quoted, to allow quantities, character sets, and grouping
            $pattern = sprintf('#%s#', $regex);
            if (preg_match($pattern, $path)) {
                // If any filter matches, we process the file.
                return true;
            }
        }

        // If no filter matches, we do not process the file.
        return false;
    }

    /**
     * @return bool True if we should process the file, false otherwise.
     */
    public function applyExclusionFilter(SplFileInfo $file)
    {
        $path = $file->getPathname();

        // Handle directories
        if ($file->isDir()) {
            foreach ($this->directoryExclusions as $exclusion) {
                if (strpos($path, $exclusion) !== false) {
                    // Matched an exclusion; do not recurse
                    return false;
                }
            }

            return true;
        }

        // Non-directory, non-files cannot be rewritten
        if (! $file->isFile()) {
            return false;
        }

        // Handle files
        foreach ($this->fileExclusions as $exclusion) {
            if (preg_match($exclusion, $path)) {
                // File matches exclusion pattern; do not process
                return false;
            }
        }

        return true;
    }

    private function prependCommonExclusions(): void
    {
        array_unshift($this->exclusions, '/.hg');
        array_unshift($this->exclusions, '/.svn');
        array_unshift($this->exclusions, '/.git');
    }

    private function prepareExclusionPatterns(): void
    {
        // Create list of directory patterns to check against
        $directory = new Directory();
        $this->directoryExclusions = array_map(static function ($exclusion) use ($directory) {
            return sprintf('/%s', trim($directory->normalizePath($exclusion), '/'));
        }, $this->exclusions);

        // Create list of filenames to check against
        $this->fileExclusions = array_map(static function ($exclusion) use ($directory) {
            $exclusion = $directory->normalizePath($exclusion);
            // Pattern is quoted, as it should be a literal. We want to match
            // files as the end segment of a path.
            return sprintf('#%s$#', preg_quote($exclusion, '#'));
        }, $this->exclusions);
    }
}
