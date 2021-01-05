<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Migration;

use Laminas\Migration\Directory;
use Laminas\Migration\FileFilter;
use PHPUnit\Framework\TestCase;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

class FileFilterTest extends TestCase
{
    public function setUp(): void
    {
        $this->cleanDirectories();
        $this->prepareDirectories();
    }

    public function tearDown(): void
    {
        $this->cleanDirectories();
    }

    public function cleanDirectories(): void
    {
        $directory = new Directory();
        $baseDir = dirname(__DIR__);
        foreach (['.hg', '.svn'] as $dir) {
            $path = sprintf('%s/%s', $baseDir, $dir);
            if (is_dir($path)) {
                $directory->rmdir($path);
            }
        }
    }

    public function prepareDirectories(): void
    {
        $baseDir = dirname(__DIR__);
        foreach (['.hg', '.svn'] as $dir) {
            $path = sprintf('%s/%s', $baseDir, $dir);
            if (file_exists($path)) {
                throw new RuntimeException(sprintf('Directory %s exists, and should not', $path));
            }
            mkdir($path);
            touch($path . '/ignore');
        }
    }

    public function getDirectoryIterator($path): RecursiveDirectoryIterator
    {
        return new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS
        );
    }

    public function testOmitsVcsDirectoriesByDefault()
    {
        $path   = realpath(dirname(__DIR__));
        $filter = new FileFilter($path, [], []);
        $files  = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                $this->getDirectoryIterator($path),
                $filter
            )
        );

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '!/\.(git|hg|svn)(/|$)!',
                $file->getRealPath(),
                'One or more files matched a VCS directory'
            );
        }
    }

    public function testDoesNotReturnFilesInExcludedDirectories()
    {
        $path   = realpath(dirname(__DIR__));
        $filter = new FileFilter($path, [], ['/vendor']);
        $files  = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                $this->getDirectoryIterator($path),
                $filter
            )
        );

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '!/vendor(/|$)!',
                $file->getRealPath(),
                'One or more files matched a VCS directory'
            );
        }
    }

    public function testDoesNotReturnExcludedFiles()
    {
        $exclusions = ['CHANGELOG.md', 'COPYRIGHT.md', 'LICENSE.md'];
        $path       = realpath(dirname(__DIR__));
        $filter     = new FileFilter($path, [], $exclusions);
        $files      = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                $this->getDirectoryIterator($path),
                $filter
            )
        );

        $pattern = implode('|', array_map(function ($exclusion) {
            return preg_quote($exclusion);
        }, $exclusions));

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '!/(' . $pattern . ')$!',
                $file->getRealPath(),
                'One or more files matched a VCS directory'
            );
        }
    }

    public function testOnlyReturnsFilesMatchingOneOrMoreRegexes()
    {
        $regexes = [
            'src/.*?',
            'config/.*?',
            '.*\.md',
        ];

        $path   = realpath(dirname(__DIR__));
        $filter = new FileFilter($path, $regexes, []);
        $files  = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                $this->getDirectoryIterator($path),
                $filter
            )
        );

        foreach ($files as $file) {
            $matches = false;
            foreach ($regexes as $regex) {
                $pattern = sprintf('#%s#', $regex);
                if (preg_match($pattern, $file->getRealPath())) {
                    $matches = true;
                    break;
                }
            }
            $this->assertTrue($matches, sprintf(
                'File "%s" did not match any regexes, but was still returned',
                $file->getRealPath()
            ));
        }
    }

    public function testOnlyReturnsFilesMatchingARegexThatAreNotAlsoExcluded()
    {
        $regexes = [
            'src/.*?',
            'config/.*?',
            '.*\.md',
        ];

        $exclusions = [
            '/vendor',
            'MigrateCommand.php',
        ];

        $path   = realpath(dirname(__DIR__));
        $filter = new FileFilter($path, $regexes, $exclusions);
        $files  = new RecursiveIteratorIterator(
            new RecursiveCallbackFilterIterator(
                $this->getDirectoryIterator($path),
                $filter
            )
        );

        $exclusionPattern = implode('|', array_map(function ($exclusion) {
            return preg_quote($exclusion);
        }, $exclusions));

        foreach ($files as $file) {
            $this->assertDoesNotMatchRegularExpression(
                '!/(' . $exclusionPattern . ')$!',
                $file->getRealPath(),
                sprintf('File %s matched a VCS directory', $file->getRealPath())
            );

            $matches = false;
            foreach ($regexes as $regex) {
                $pattern = sprintf('#%s#', $regex);
                if (preg_match($pattern, $file->getRealPath())) {
                    $matches = true;
                    break;
                }
            }
            $this->assertTrue($matches, sprintf(
                'File "%s" did not match any regexes, but was still returned',
                $file->getRealPath()
            ));
        }
    }
}
