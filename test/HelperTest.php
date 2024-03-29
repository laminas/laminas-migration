<?php

declare(strict_types=1);

namespace LaminasTest\Migration;

use Laminas\Migration\Helper;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function file_get_contents;
use function json_decode;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

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
            'name' => 'Elan Ruusamäe',
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
