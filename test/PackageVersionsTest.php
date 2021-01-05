<?php

declare(strict_types=1);

namespace LaminasTest\Migration;

use Laminas\Migration\PackageVersions;
use PHPUnit\Framework\TestCase;

class PackageVersionsTest extends TestCase
{
    public function testShouldProvideVersionForKnownPackages(): void
    {
        $this->assertSame(
            '1.0.0',
            (new PackageVersions(['foo/bar' => '1.0.0']))->getPackageVersion('foo/bar')
        );
    }

    public function testShouldProvideUnknownForUnknownPackages(): void
    {
        $this->assertSame(
            'UNKNOWN',
            (new PackageVersions([]))->getPackageVersion('foo/bar')
        );
    }

    /**
     * @psalm-return iterable<string, array{
     *     0: array<array-key, string>,
     *     1: string
     * }>
     */
    public function provideComposerFilesTestData(): iterable
    {
        return [
            'both-present' => [
                [
                    __DIR__ . '/composer-fixtures/installed.json',
                    __DIR__ . '/composer-fixtures/composer.lock',
                ],
                '1.0.2'
            ],
            'lock-missing' => [
                [
                    __DIR__ . '/composer-fixtures/installed.json',
                    __DIR__ . '/composer-fixtures/missing-composer.lock',
                ],
                '1.0.2'
            ],
            'installed-missing' => [
                [
                    __DIR__ . '/composer-fixtures/missing-installed.json',
                    __DIR__ . '/composer-fixtures/composer.lock',
                ],
                '1.0.2'
            ],
            'both-missing' => [
                [
                    __DIR__ . '/composer-fixtures/missing-installed.json',
                    __DIR__ . '/composer-fixtures/missing-composer.lock',
                ],
                'UNKNOWN'
            ],
            'only-one-provided' => [
                [
                    __DIR__ . '/composer-fixtures/installed.json',
                ],
                '1.0.2'
            ],
            'empty-list' => [
                [],
                'UNKNOWN'
            ],
        ];
    }

    /**
     * @dataProvider provideComposerFilesTestData
     * @param string[] $composerFiles
     */
    public function testShouldBuildFromComposerFiles(array $composerFiles, string $expectedVersion): void
    {
        $versions = PackageVersions::fromComposerFiles($composerFiles);

        $this->assertSame(
            $expectedVersion,
            $versions->getPackageVersion(PackageVersions::APP_PACKAGE_NAME)
        );
    }

    public function testShouldAlwaysProvideAppVersion(): void
    {
        // Testing such a static, env-based method is not simple. We cannot ensure a specific environment
        // so it can only check whether the method returns a non-empty string

        $version = PackageVersions::getAppVersion();

        $this->assertNotEmpty($version);
    }
}
