<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Migration;

use Laminas\Migration\Helper;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

class HelperTest extends TestCase
{
    public function edgeCases(): iterable
    {
        yield 'Example class' => [
            file_get_contents(__DIR__ . '/TestAsset/TestClass.php'),
            file_get_contents(__DIR__ . '/TestAsset/TestClass.php.out'),
        ];
        yield 'Apigility module' => ['ZF\Apigility', 'Apigility'];
        yield 'Apigility documentation module' => ['ZF\Apigility\Documentation', 'Apigility\Documentation'];
        yield 'Apigility modules.config.php' => [
            file_get_contents(__DIR__ . '/TestAsset/edge-case-apigility-modules.php'),
            file_get_contents(__DIR__ . '/TestAsset/edge-case-apigility-modules.php.out'),
        ];
    }

    /**
     * @dataProvider edgeCases
     * @param string $string
     * @param string $expected
     */
    public function testEdgeCases($string, $expected): void
    {
        $this->assertSame($expected, Helper::replace($string));
    }
}
