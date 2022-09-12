<?php

declare(strict_types=1);

namespace LaminasTest\Migration\SpecialCase;

use Laminas\Migration\SpecialCase\ComposerJsonExtraZFSpecialCase;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function json_decode;
use function sprintf;

class ComposerJsonExtraZFSpecialCaseTest extends TestCase
{
    public function testReplacesZendFrameworkPackageWithAppropriatePackagesAtAppropriateConstraint(): void
    {
        $specialCase      = new ComposerJsonExtraZFSpecialCase();
        $file             = sprintf('%s/fixtures/extra/composer.json', __DIR__);
        $expectedFile     = sprintf('%s/fixtures/extra/composer.updated.json', __DIR__);
        $contents         = file_get_contents($file);
        $expectedContents = file_get_contents($expectedFile);
        $expected         = json_decode($expectedContents, true);

        $this->assertTrue($specialCase->matches($file, $contents));

        $result       = $specialCase->replace($contents);
        $replacements = json_decode($result, true);

        $this->assertEquals($expected, $replacements);
    }
}
