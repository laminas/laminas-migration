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
     * @param array $packageList Package/Version pairs
     * @param string $constraint Original constraint
     * @return array The list of packages, with appropriate constraints.
     */
    private function normalizeConstraints(array $packageList, $constraint)
    {
        foreach ($packageList as $package => $version) {
            if ($version === 'self.version') {
                $packageList[$package] = $constraint;
            }
        }
        return $packageList;
    }

    /**
     * @return iterable
     */
    public function expectedReplacements()
    {
        // phpcs:disable
        yield '2.0.0'   => ['2.0.0', '2.0.0', $this->normalizeConstraints(ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_0_0__2_0_6, '2.0.0')];
        yield '2.0.7'   => ['2.0.7', '2.0.7', $this->normalizeConstraints(ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_0_7__2_0_8, '2.0.7')];
        yield '2.1.0'   => ['2.1.0', '2.1.0', $this->normalizeConstraints(ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_1_0__2_2_1, '2.1.0')];
        yield '2.2.2'   => ['2.2.2', '2.2.2', $this->normalizeConstraints(ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_2_2__2_4_13, '2.2.2')];
        yield '2.5.0'   => ['2.5.0', '2.5.0', ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_5_0];
        yield '2.5.1'   => ['2.5.1', '2.5.1', ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_5_1];
        yield '2.5.2'   => ['2.5.2', '2.5.2', ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_5_2];
        yield '2.5.3'   => ['2.5.3', '2.5.3', ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_5_3];
        yield '3.0.0'   => ['3.0.0', '3.0.0', ComposerJsonZendFrameworkPackageSpecialCase::ZF_3_0_0];
        yield '~2.4.13' => ['~2.4.13', 'semantic-2.4.13', $this->normalizeConstraints(ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_2_2__2_4_13, '2.4.13')];
        yield '~2.5.0'  => ['~2.5.0', 'semantic-2.5.0', ComposerJsonZendFrameworkPackageSpecialCase::ZF_2_5_3];
        yield '~3.0.0'  => ['~3.0.0', 'semantic-3.0.0', ComposerJsonZendFrameworkPackageSpecialCase::ZF_3_0_0];
        // phpcs:enable
    }

    /**
     * @dataProvider expectedReplacements
     * @param string $constraint
     * @param string $directory
     * @param array $expectedPackageList
     */
    public function testReplacesZendFrameworkPackageWithAppropriatePackagesAtAppropriateConstraint(
        $constraint,
        $directory,
        array $expectedPackageList
    ) {
        $specialCase = new ComposerJsonZendFrameworkPackageSpecialCase();
        $file        = sprintf('%s/fixtures/%s/composer.json', __DIR__, $directory);
        $contents    = file_get_contents($file);

        $this->assertTrue($specialCase->matches($file, $contents));

        $replacement = $specialCase->replace($contents);

        $this->assertNotRegExp('#"zendframework/zendframework":#s', $replacement);

        foreach ($expectedPackageList as $package => $constraint) {
            $expected = sprintf('#"%s": "%s"#s', preg_quote($package), preg_quote($constraint));
            $this->assertRegExp($expected, $replacement);
        }
    }
}
