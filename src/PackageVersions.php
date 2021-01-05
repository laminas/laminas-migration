<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

use function array_filter;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_shift;
use function assert;
use function file_get_contents;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;

/**
 * @internal
 */
final class PackageVersions
{
    public const COMPOSER_INSTALLED = __DIR__ . '/../../../../composer/installed.json';
    public const COMPOSER_LOCK = __DIR__ . '/../../../../../composer.lock';
    public const APP_PACKAGE_NAME = 'laminas/laminas-migration';

    /**
     * @var array<string, string>
     */
    private $packages;

    /**
     * @param array<string, string> $packages
     */
    public function __construct(array $packages)
    {
        $this->packages = $packages;
    }

    /**
     * Will build the version map from all given composer files
     *
     * @param string[] $composerFiles
     * @return self
     */
    public static function fromComposerFiles(array $composerFiles)
    {
        $allPackageLists = array_map(
            static function ($filename): array {
                $data = json_decode(file_get_contents($filename), false);

                if (is_array($data)) {
                    return self::buildMapFromPackageList($data);
                }

                assert(is_object($data) && isset($data->packages) && is_array($data->packages));
                assert(! isset($data->{'packages-dev'}) || is_array($data->{'packages-dev'}));

                return self::buildMapFromPackageList(
                    $data->packages + (isset($data->{'packages-dev'}) ? $data->{'packages-dev'} : [])
                );
            },
            array_filter(
                $composerFiles,
                'is_readable'
            )
        );

        if (count($allPackageLists) <= 1) {
            return new self($allPackageLists ? array_shift($allPackageLists) : []);
        }

        return new self(array_merge(...$allPackageLists));
    }

    /**
     * @return string
     */
    public static function getAppVersion()
    {
        return self::fromComposerFiles([self::COMPOSER_INSTALLED, self::COMPOSER_LOCK])
            ->getPackageVersion(self::APP_PACKAGE_NAME);
    }

    /**
     * @param string $packageName
     * @return string
     */
    public function getPackageVersion($packageName)
    {
        return isset($this->packages[$packageName])
            ? $this->packages[$packageName]
            : 'UNKNOWN';
    }

    /**
     * @param object[] $composerPackageList
     *
     * @return (mixed|string)[]
     *
     * @psalm-return array<array-key, mixed|string>
     */
    private static function buildMapFromPackageList(array $composerPackageList): array
    {
        return array_reduce(
            $composerPackageList,
            static function (array $result, object $package) {
                assert(isset($package->name) && is_string($package->name));
                assert(isset($package->version) && is_string($package->version));

                $result[$package->name] = $package->version;

                return $result;
            },
            []
        );
    }
}
