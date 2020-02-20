<?php

declare(strict_types=1);

namespace LaminasTest\Migration;

use Laminas\Migration\PackageVersions;
use PHPUnit\Framework\TestCase;

class PackageVersionsTest extends TestCase
{
    public function testShouldProvideVersionForKnownPackages(): void
    {
        self::assertSame(
            '1.0.0',
            (new PackageVersions(['foo/bar' => '1.0.0']))->getPackageVersion('foo/bar')
        );
    }

    public function testShouldProvideUnknownForUnknownPackages(): void
    {
        self::assertSame(
            'UNKNOWN',
            (new PackageVersions([]))->getPackageVersion('foo/bar')
        );
    }

    public function testShouldBuildFromComposerFiles(): void
    {
        $versions = PackageVersions::fromComposerFiles([
            __DIR__ . '/composer-fixtures/installed.json',
            __DIR__ . '/composer-fixtures/missing.json',
            __DIR__ . '/composer-fixtures/composer.lock',
        ]);

        self::assertSame(
            '1.0.2',
            $versions->getPackageVersion(PackageVersions::APP_PACKAGE_NAME)
        );
    }
}
