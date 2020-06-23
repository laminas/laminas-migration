<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Migration;

use Laminas\Migration\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    /** @var string */
    private $tempfile;

    public function setUp(): void
    {
        $this->tempfile = tempnam(sys_get_temp_dir(), 'lfm');
    }

    public function tearDown(): void
    {
        if (file_exists($this->tempfile)) {
            unlink($this->tempfile);
        }
    }

    public function testHelperWritesJsonFileWithoutEscapingUnicode(): void
    {
        $data = [
            'name'  => 'Elan Ruusamäe',
        ];

        Helper::writeJson($this->tempfile, $data);

        $json = file_get_contents($this->tempfile);

        // This assertion verifies the JSON written to the file has no escaping
        $this->assertStringContainsString($data['name'], $json);

        // This assertion verifies the parsed data is the same
        $parsed = json_decode($json, true);
        $this->assertSame($data, $parsed);
    }
}
