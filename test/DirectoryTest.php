<?php

namespace LaminasTest\Migration;

use Laminas\Migration\Directory;
use PHPUnit\Framework\TestCase;

class DirectoryTest extends TestCase
{
    /**
     * @psalm-return iterable<string, array{
     *     0: string,
     *     1: string
     * }>
     */
    public function pathsToNormalize(): iterable
    {
        yield 'unix'    => ['/home/user/project', '/home/user/project'];
        yield 'windows' => ['C:\Users\user\project', 'C:/Users/user/project'];
    }

    /**
     * @dataProvider pathsToNormalize
     */
    public function testNormalizePathNormalizesToUnixPaths(string $original, string $expected): void
    {
        $dir = new Directory();
        $this->assertSame($expected, $dir->normalizePath($original));
    }
}
