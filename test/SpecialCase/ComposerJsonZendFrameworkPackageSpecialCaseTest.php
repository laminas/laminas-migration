<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Migration\SpecialCase;

use Laminas\Migration\SpecialCase\ComposerJsonZendFrameworkPackageSpecialCase;
use PHPUnit\Framework\TestCase;

class ComposerJsonZendFrameworkPackageSpecialCaseTest extends TestCase
{
    /**
     * @return iterable
     */
    public function expectedReplacements()
    {
        // phpcs:disable
        yield '2.0.0'              => ['2.0.0'];
        yield '2.0.7'              => ['2.0.7'];
        yield '2.1.0'              => ['2.1.0'];
        yield '2.2.2'              => ['2.2.2'];
        yield '2.5.0'              => ['2.5.0'];
        yield '2.5.1'              => ['2.5.1'];
        yield '2.5.2'              => ['2.5.2'];
        yield '2.5.3'              => ['2.5.3'];
        yield '2.5.3-case-variant' => ['2.5.3-case-variant'];
        yield '3.0.0'              => ['3.0.0'];
        yield '3.0.0-dev'          => ['3.0.0-dev'];
        yield '~2.4.13'            => ['semantic-2.4.13'];
        yield '~2.5.0'             => ['semantic-2.5.0'];
        yield '~3.0.0'             => ['semantic-3.0.0'];
        // phpcs:enable
    }

    /**
     * @dataProvider expectedReplacements
     *
     * @param string $directory
     *
     * @return void
     */
    public function testReplacesZendFrameworkPackageWithAppropriatePackagesAtAppropriateConstraint($directory): void
    {
        $specialCase      = new ComposerJsonZendFrameworkPackageSpecialCase();
        $file             = sprintf('%s/fixtures/%s/composer.json', __DIR__, $directory);
        $expectedFile     = sprintf('%s/fixtures/%s/composer.updated.json', __DIR__, $directory);
        $contents         = file_get_contents($file);
        $expectedContents = file_get_contents($expectedFile);
        $expected         = json_decode($expectedContents, true);

        $this->assertTrue($specialCase->matches($file, $contents));

        $result       = $specialCase->replace($contents);
        $replacements = json_decode($result, true);

        $this->assertEquals($expected, $replacements);
    }
}
