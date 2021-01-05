<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration\SpecialCase;

use Composer\Semver\Semver;
use RuntimeException;

class ComposerJsonZendFrameworkPackageSpecialCase implements SpecialCaseInterface
{
    public function matches($filename, $content)
    {
        if (! preg_match('#\bcomposer\.json$#', $filename)) {
            return false;
        }

        $composer = $this->normalizePackageNames(json_decode($content, true));
        return isset($composer['require']['zendframework/zendframework'])
            || isset($composer['require-dev']['zendframework/zendframework']);
    }

    public function replace($content)
    {
        $composer = $this->normalizePackageNames(json_decode($content, true));

        $isProduction = isset($composer['require']['zendframework/zendframework']);

        $section = isset($composer['require']['zendframework/zendframework'])
            ? 'require'
            : 'require-dev';

        $packages = $this->getPackageList($composer[$section]['zendframework/zendframework']);
        $composer = $this->updateComposer($composer, $packages, $section);

        return json_encode($composer, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @return array
     */
    private function normalizePackageNames(array $composer)
    {
        foreach (['require', 'require-dev'] as $section) {
            if (! isset($composer[$section])) {
                continue;
            }

            foreach ($composer[$section] as $package => $constraint) {
                $normalized = strtolower($package);
                if ($normalized === $package) {
                    continue;
                }

                unset($composer[$section][$package]);
                $composer[$section][$normalized] = $constraint;
            }
        }
        return $composer;
    }

    /**
     * @param string $constraint
     * @return array
     * @throws RuntimeException
     */
    private function getPackageList($constraint)
    {
        $semver   = new Semver();
        $versions = array_keys(self::ZF_VERSIONS);
        usort($versions, static function ($a, $b) {
            if (! is_string($a) || ! is_string($b)) {
                return $b <=> $a;
            }

            // Reverse sort by version
            $result = version_compare($a, $b);
            if ($result === 0) {
                return 0;
            }
            if ($result === 1) {
                return -1;
            }
            return 1;
        });

        foreach ($versions as $version) {
            if ($semver->satisfies($version, $constraint)) {
                $packageList = self::ZF_VERSIONS[$version];
                return $this->normalizeConstraints($packageList, $version, $semver);
            }
        }

        throw new RuntimeException(sprintf(
            'Discovered unknown zendframework/zendframework constraint "%s"; cannot replace package',
            $constraint
        ));
    }

    /**
     * @param array $packageList Package/Version pairs
     * @param string $constraint Original constraint
     * @return array The list of packages, with appropriate constraints.
     */
    private function normalizeConstraints(array $packageList, $constraint, Semver $semver)
    {
        foreach ($packageList as $package => $version) {
            if ($version === 'self.version') {
                $packageList[$package] = $constraint;
            }
        }
        return $packageList;
    }

    /**
     * @param string $section Either "require" or "require-dev"
     * @return array The updated Composer array.
     */
    private function updateComposer(array $composer, array $packages, $section)
    {
        unset($composer[$section]['zendframework/zendframework']);
        $composer[$section] = array_merge($composer[$section], $packages);

        // Sort the section.
        // Items not in vendor/package format bubble up.
        uksort($composer[$section], static function ($a, $b) {
            if (! is_string($a) || ! is_string($b)) {
                return $a <=> $b;
            }

            if (strpos($a, '/') === false && strpos($b, '/') === false) {
                return strcasecmp($a, $b);
            }

            if (strpos($a, '/') === false && strpos($b, '/') !== false) {
                return -1;
            }

            if (strpos($a, '/') !== false && strpos($b, '/') === false) {
                return 1;
            }

            return strcasecmp($a, $b);
        });

        return $composer;
    }

    public const ZF_VERSIONS = [
        '2.0.0'  => self::ZF_2_0_0__2_0_6,
        '2.0.1'  => self::ZF_2_0_0__2_0_6,
        '2.0.2'  => self::ZF_2_0_0__2_0_6,
        '2.0.3'  => self::ZF_2_0_0__2_0_6,
        '2.0.4'  => self::ZF_2_0_0__2_0_6,
        '2.0.5'  => self::ZF_2_0_0__2_0_6,
        '2.0.6'  => self::ZF_2_0_0__2_0_6,
        '2.0.7'  => self::ZF_2_0_7__2_0_8,
        '2.0.8'  => self::ZF_2_0_7__2_0_8,
        '2.1.0'  => self::ZF_2_1_0__2_2_1,
        '2.1.1'  => self::ZF_2_1_0__2_2_1,
        '2.1.2'  => self::ZF_2_1_0__2_2_1,
        '2.1.3'  => self::ZF_2_1_0__2_2_1,
        '2.1.4'  => self::ZF_2_1_0__2_2_1,
        '2.1.5'  => self::ZF_2_1_0__2_2_1,
        '2.1.6'  => self::ZF_2_1_0__2_2_1,
        '2.2.0'  => self::ZF_2_1_0__2_2_1,
        '2.2.1'  => self::ZF_2_1_0__2_2_1,
        '2.2.2'  => self::ZF_2_2_2__2_4_13,
        '2.2.3'  => self::ZF_2_2_2__2_4_13,
        '2.2.4'  => self::ZF_2_2_2__2_4_13,
        '2.2.5'  => self::ZF_2_2_2__2_4_13,
        '2.2.6'  => self::ZF_2_2_2__2_4_13,
        '2.2.7'  => self::ZF_2_2_2__2_4_13,
        '2.2.8'  => self::ZF_2_2_2__2_4_13,
        '2.2.9'  => self::ZF_2_2_2__2_4_13,
        '2.2.10' => self::ZF_2_2_2__2_4_13,
        '2.3.0'  => self::ZF_2_2_2__2_4_13,
        '2.3.1'  => self::ZF_2_2_2__2_4_13,
        '2.3.2'  => self::ZF_2_2_2__2_4_13,
        '2.3.3'  => self::ZF_2_2_2__2_4_13,
        '2.3.4'  => self::ZF_2_2_2__2_4_13,
        '2.3.5'  => self::ZF_2_2_2__2_4_13,
        '2.3.6'  => self::ZF_2_2_2__2_4_13,
        '2.3.7'  => self::ZF_2_2_2__2_4_13,
        '2.3.8'  => self::ZF_2_2_2__2_4_13,
        '2.3.9'  => self::ZF_2_2_2__2_4_13,
        '2.4.0'  => self::ZF_2_2_2__2_4_13,
        '2.4.1'  => self::ZF_2_2_2__2_4_13,
        '2.4.2'  => self::ZF_2_2_2__2_4_13,
        '2.4.3'  => self::ZF_2_2_2__2_4_13,
        '2.4.4'  => self::ZF_2_2_2__2_4_13,
        '2.4.5'  => self::ZF_2_2_2__2_4_13,
        '2.4.6'  => self::ZF_2_2_2__2_4_13,
        '2.4.7'  => self::ZF_2_2_2__2_4_13,
        '2.4.8'  => self::ZF_2_2_2__2_4_13,
        '2.4.9'  => self::ZF_2_2_2__2_4_13,
        '2.4.10' => self::ZF_2_2_2__2_4_13,
        '2.4.11' => self::ZF_2_2_2__2_4_13,
        '2.4.12' => self::ZF_2_2_2__2_4_13,
        '2.4.13' => self::ZF_2_2_2__2_4_13,
        '2.5.0'  => self::ZF_2_5_0,
        '2.5.1'  => self::ZF_2_5_1,
        '2.5.2'  => self::ZF_2_5_2,
        '2.5.3'  => self::ZF_2_5_3,
        '3.0.0'  => self::ZF_3_0_0,
    ];

    public const ZF_2_0_0__2_0_6 = [
        'zendframework/zend-acl'            => 'self.version',
        'zendframework/zend-authentication' => 'self.version',
        'zendframework/zend-barcode'        => 'self.version',
        'zendframework/zend-cache'          => 'self.version',
        'zendframework/zend-captcha'        => 'self.version',
        'zendframework/zend-code'           => 'self.version',
        'zendframework/zend-config'         => 'self.version',
        'zendframework/zend-console'        => 'self.version',
        'zendframework/zend-crypt'          => 'self.version',
        'zendframework/zend-db'             => 'self.version',
        'zendframework/zend-di'             => 'self.version',
        'zendframework/zend-dom'            => 'self.version',
        'zendframework/zend-escaper'        => 'self.version',
        'zendframework/zend-eventmanager'   => 'self.version',
        'zendframework/zend-feed'           => 'self.version',
        'zendframework/zend-file'           => 'self.version',
        'zendframework/zend-filter'         => 'self.version',
        'zendframework/zend-form'           => 'self.version',
        'zendframework/zend-http'           => 'self.version',
        'zendframework/zend-i18n'           => 'self.version',
        'zendframework/zend-inputfilter'    => 'self.version',
        'zendframework/zend-json'           => 'self.version',
        'zendframework/zend-ldap'           => 'self.version',
        'zendframework/zend-loader'         => 'self.version',
        'zendframework/zend-log'            => 'self.version',
        'zendframework/zend-mail'           => 'self.version',
        'zendframework/zend-markup'         => 'self.version',
        'zendframework/zend-math'           => 'self.version',
        'zendframework/zend-memory'         => 'self.version',
        'zendframework/zend-mime'           => 'self.version',
        'zendframework/zend-modulemanager'  => 'self.version',
        'zendframework/zend-mvc'            => 'self.version',
        'zendframework/zend-navigation'     => 'self.version',
        'zendframework/zend-paginator'      => 'self.version',
        'zendframework/zend-progressbar'    => 'self.version',
        'zendframework/zend-serializer'     => 'self.version',
        'zendframework/zend-server'         => 'self.version',
        'zendframework/zend-servicemanager' => 'self.version',
        'zendframework/zend-session'        => 'self.version',
        'zendframework/zend-soap'           => 'self.version',
        'zendframework/zend-stdlib'         => 'self.version',
        'zendframework/zend-tag'            => 'self.version',
        'zendframework/zend-text'           => 'self.version',
        'zendframework/zend-uri'            => 'self.version',
        'zendframework/zend-validator'      => 'self.version',
        'zendframework/zend-view'           => 'self.version',
        'zendframework/zend-xmlrpc'         => 'self.version',
    ];

    public const ZF_2_0_7__2_0_8 = [
        'zendframework/zend-authentication'  => 'self.version',
        'zendframework/zend-barcode'         => 'self.version',
        'zendframework/zend-cache'           => 'self.version',
        'zendframework/zend-captcha'         => 'self.version',
        'zendframework/zend-code'            => 'self.version',
        'zendframework/zend-config'          => 'self.version',
        'zendframework/zend-console'         => 'self.version',
        'zendframework/zend-crypt'           => 'self.version',
        'zendframework/zend-db'              => 'self.version',
        'zendframework/zend-debug'           => 'self.version',
        'zendframework/zend-di'              => 'self.version',
        'zendframework/zend-dom'             => 'self.version',
        'zendframework/zend-escaper'         => 'self.version',
        'zendframework/zend-eventmanager'    => 'self.version',
        'zendframework/zend-feed'            => 'self.version',
        'zendframework/zend-file'            => 'self.version',
        'zendframework/zend-filter'          => 'self.version',
        'zendframework/zend-form'            => 'self.version',
        'zendframework/zend-http'            => 'self.version',
        'zendframework/zend-i18n'            => 'self.version',
        'zendframework/zend-inputfilter'     => 'self.version',
        'zendframework/zend-json'            => 'self.version',
        'zendframework/zend-ldap'            => 'self.version',
        'zendframework/zend-loader'          => 'self.version',
        'zendframework/zend-log'             => 'self.version',
        'zendframework/zend-mail'            => 'self.version',
        'zendframework/zend-math'            => 'self.version',
        'zendframework/zend-memory'          => 'self.version',
        'zendframework/zend-mime'            => 'self.version',
        'zendframework/zend-modulemanager'   => 'self.version',
        'zendframework/zend-mvc'             => 'self.version',
        'zendframework/zend-navigation'      => 'self.version',
        'zendframework/zend-paginator'       => 'self.version',
        'zendframework/zend-permissions-acl' => 'self.version',
        'zendframework/zend-progressbar'     => 'self.version',
        'zendframework/zend-serializer'      => 'self.version',
        'zendframework/zend-server'          => 'self.version',
        'zendframework/zend-servicemanager'  => 'self.version',
        'zendframework/zend-session'         => 'self.version',
        'zendframework/zend-soap'            => 'self.version',
        'zendframework/zend-stdlib'          => 'self.version',
        'zendframework/zend-tag'             => 'self.version',
        'zendframework/zend-text'            => 'self.version',
        'zendframework/zend-uri'             => 'self.version',
        'zendframework/zend-validator'       => 'self.version',
        'zendframework/zend-version'         => 'self.version',
        'zendframework/zend-view'            => 'self.version',
        'zendframework/zend-xmlrpc'          => 'self.version',
    ];

    public const ZF_2_1_0__2_2_1 = [
        'zendframework/zend-authentication'   => 'self.version',
        'zendframework/zend-barcode'          => 'self.version',
        'zendframework/zend-cache'            => 'self.version',
        'zendframework/zend-captcha'          => 'self.version',
        'zendframework/zend-code'             => 'self.version',
        'zendframework/zend-config'           => 'self.version',
        'zendframework/zend-console'          => 'self.version',
        'zendframework/zend-crypt'            => 'self.version',
        'zendframework/zend-db'               => 'self.version',
        'zendframework/zend-debug'            => 'self.version',
        'zendframework/zend-di'               => 'self.version',
        'zendframework/zend-dom'              => 'self.version',
        'zendframework/zend-escaper'          => 'self.version',
        'zendframework/zend-eventmanager'     => 'self.version',
        'zendframework/zend-feed'             => 'self.version',
        'zendframework/zend-file'             => 'self.version',
        'zendframework/zend-filter'           => 'self.version',
        'zendframework/zend-form'             => 'self.version',
        'zendframework/zend-http'             => 'self.version',
        'zendframework/zend-i18n'             => 'self.version',
        'zendframework/zend-inputfilter'      => 'self.version',
        'zendframework/zend-json'             => 'self.version',
        'zendframework/zend-ldap'             => 'self.version',
        'zendframework/zend-loader'           => 'self.version',
        'zendframework/zend-log'              => 'self.version',
        'zendframework/zend-mail'             => 'self.version',
        'zendframework/zend-math'             => 'self.version',
        'zendframework/zend-memory'           => 'self.version',
        'zendframework/zend-mime'             => 'self.version',
        'zendframework/zend-modulemanager'    => 'self.version',
        'zendframework/zend-mvc'              => 'self.version',
        'zendframework/zend-navigation'       => 'self.version',
        'zendframework/zend-paginator'        => 'self.version',
        'zendframework/zend-permissions-acl'  => 'self.version',
        'zendframework/zend-permissions-rbac' => 'self.version',
        'zendframework/zend-progressbar'      => 'self.version',
        'zendframework/zend-serializer'       => 'self.version',
        'zendframework/zend-server'           => 'self.version',
        'zendframework/zend-servicemanager'   => 'self.version',
        'zendframework/zend-session'          => 'self.version',
        'zendframework/zend-soap'             => 'self.version',
        'zendframework/zend-stdlib'           => 'self.version',
        'zendframework/zend-tag'              => 'self.version',
        'zendframework/zend-test'             => 'self.version',
        'zendframework/zend-text'             => 'self.version',
        'zendframework/zend-uri'              => 'self.version',
        'zendframework/zend-validator'        => 'self.version',
        'zendframework/zend-version'          => 'self.version',
        'zendframework/zend-view'             => 'self.version',
        'zendframework/zend-xmlrpc'           => 'self.version',
    ];

    public const ZF_2_2_2__2_4_13 = [
        'zendframework/zend-authentication'   => 'self.version',
        'zendframework/zend-barcode'          => 'self.version',
        'zendframework/zend-cache'            => 'self.version',
        'zendframework/zend-captcha'          => 'self.version',
        'zendframework/zend-code'             => 'self.version',
        'zendframework/zend-config'           => 'self.version',
        'zendframework/zend-console'          => 'self.version',
        'zendframework/zend-crypt'            => 'self.version',
        'zendframework/zend-db'               => 'self.version',
        'zendframework/zend-debug'            => 'self.version',
        'zendframework/zend-di'               => 'self.version',
        'zendframework/zend-dom'              => 'self.version',
        'zendframework/zend-escaper'          => 'self.version',
        'zendframework/zend-eventmanager'     => 'self.version',
        'zendframework/zend-feed'             => 'self.version',
        'zendframework/zend-file'             => 'self.version',
        'zendframework/zend-filter'           => 'self.version',
        'zendframework/zend-form'             => 'self.version',
        'zendframework/zend-http'             => 'self.version',
        'zendframework/zend-i18n'             => 'self.version',
        'zendframework/zend-i18n-resources'   => 'self.version',
        'zendframework/zend-inputfilter'      => 'self.version',
        'zendframework/zend-json'             => 'self.version',
        'zendframework/zend-ldap'             => 'self.version',
        'zendframework/zend-loader'           => 'self.version',
        'zendframework/zend-log'              => 'self.version',
        'zendframework/zend-mail'             => 'self.version',
        'zendframework/zend-math'             => 'self.version',
        'zendframework/zend-memory'           => 'self.version',
        'zendframework/zend-mime'             => 'self.version',
        'zendframework/zend-modulemanager'    => 'self.version',
        'zendframework/zend-mvc'              => 'self.version',
        'zendframework/zend-navigation'       => 'self.version',
        'zendframework/zend-paginator'        => 'self.version',
        'zendframework/zend-permissions-acl'  => 'self.version',
        'zendframework/zend-permissions-rbac' => 'self.version',
        'zendframework/zend-progressbar'      => 'self.version',
        'zendframework/zend-serializer'       => 'self.version',
        'zendframework/zend-server'           => 'self.version',
        'zendframework/zend-servicemanager'   => 'self.version',
        'zendframework/zend-session'          => 'self.version',
        'zendframework/zend-soap'             => 'self.version',
        'zendframework/zend-stdlib'           => 'self.version',
        'zendframework/zend-tag'              => 'self.version',
        'zendframework/zend-test'             => 'self.version',
        'zendframework/zend-text'             => 'self.version',
        'zendframework/zend-uri'              => 'self.version',
        'zendframework/zend-validator'        => 'self.version',
        'zendframework/zend-version'          => 'self.version',
        'zendframework/zend-view'             => 'self.version',
        'zendframework/zend-xmlrpc'           => 'self.version',
    ];

    public const ZF_2_5_0 = [
        'zendframework/zend-authentication'   => '~2.5.0',
        'zendframework/zend-barcode'          => '~2.5.0',
        'zendframework/zend-cache'            => '~2.5.0',
        'zendframework/zend-captcha'          => '~2.5.0',
        'zendframework/zend-code'             => '~2.5.0',
        'zendframework/zend-config'           => '~2.5.0',
        'zendframework/zend-console'          => '~2.5.0',
        'zendframework/zend-crypt'            => '~2.5.0',
        'zendframework/zend-db'               => '~2.5.0',
        'zendframework/zend-debug'            => '~2.5.0',
        'zendframework/zend-di'               => '~2.5.0',
        'zendframework/zend-dom'              => '~2.5.0',
        'zendframework/zend-escaper'          => '~2.5.0',
        'zendframework/zend-eventmanager'     => '~2.5.0',
        'zendframework/zend-feed'             => '~2.5.0',
        'zendframework/zend-file'             => '~2.5.0',
        'zendframework/zend-filter'           => '~2.5.0',
        'zendframework/zend-form'             => '~2.5.0',
        'zendframework/zend-http'             => '~2.5.0',
        'zendframework/zend-i18n'             => '~2.5.0',
        'zendframework/zend-i18n-resources'   => '~2.5.0',
        'zendframework/zend-inputfilter'      => '~2.5.0',
        'zendframework/zend-json'             => '~2.5.0',
        'zendframework/zend-ldap'             => '~2.5.0',
        'zendframework/zend-loader'           => '~2.5.0',
        'zendframework/zend-log'              => '~2.5.0',
        'zendframework/zend-mail'             => '~2.5.0',
        'zendframework/zend-math'             => '~2.5.0',
        'zendframework/zend-memory'           => '~2.5.0',
        'zendframework/zend-mime'             => '~2.5.0',
        'zendframework/zend-modulemanager'    => '~2.5.0',
        'zendframework/zend-mvc'              => '~2.5.0',
        'zendframework/zend-navigation'       => '~2.5.0',
        'zendframework/zend-paginator'        => '~2.5.0',
        'zendframework/zend-permissions-acl'  => '~2.5.0',
        'zendframework/zend-permissions-rbac' => '~2.5.0',
        'zendframework/zend-progressbar'      => '~2.5.0',
        'zendframework/zend-serializer'       => '~2.5.0',
        'zendframework/zend-server'           => '~2.5.0',
        'zendframework/zend-servicemanager'   => '~2.5.0',
        'zendframework/zend-session'          => '~2.5.0',
        'zendframework/zend-soap'             => '~2.5.0',
        'zendframework/zend-stdlib'           => '~2.5.0',
        'zendframework/zend-tag'              => '~2.5.0',
        'zendframework/zend-test'             => '~2.5.0',
        'zendframework/zend-text'             => '~2.5.0',
        'zendframework/zend-uri'              => '~2.5.0',
        'zendframework/zend-validator'        => '~2.5.0',
        'zendframework/zend-version'          => '~2.5.0',
        'zendframework/zend-view'             => '~2.5.0',
        'zendframework/zend-xmlrpc'           => '~2.5.0',
        'zendframework/zendxml'               => '~1.0',
    ];

    public const ZF_2_5_1 = [
        'zendframework/zend-authentication'   => '~2.5.0',
        'zendframework/zend-barcode'          => '~2.5.0',
        'zendframework/zend-cache'            => '~2.5.0',
        'zendframework/zend-captcha'          => '~2.5.0',
        'zendframework/zend-code'             => '~2.5.0',
        'zendframework/zend-config'           => '~2.5.0',
        'zendframework/zend-console'          => '~2.5.0',
        'zendframework/zend-crypt'            => '~2.5.0',
        'zendframework/zend-db'               => '~2.5.0',
        'zendframework/zend-debug'            => '~2.5.0',
        'zendframework/zend-di'               => '~2.5.0',
        'zendframework/zend-dom'              => '~2.5.0',
        'zendframework/zend-escaper'          => '~2.5.0',
        'zendframework/zend-eventmanager'     => '~2.5.0',
        'zendframework/zend-feed'             => '~2.5.0',
        'zendframework/zend-file'             => '~2.5.0',
        'zendframework/zend-filter'           => '~2.5.0',
        'zendframework/zend-form'             => '~2.5.0',
        'zendframework/zend-http'             => '~2.5.0',
        'zendframework/zend-i18n'             => '~2.5.0',
        'zendframework/zend-i18n-resources'   => '~2.5.0',
        'zendframework/zend-inputfilter'      => '~2.5.0',
        'zendframework/zend-json'             => '~2.5.0',
        'zendframework/zend-loader'           => '~2.5.0',
        'zendframework/zend-log'              => '~2.5.0',
        'zendframework/zend-mail'             => '~2.5.0',
        'zendframework/zend-math'             => '~2.5.0',
        'zendframework/zend-memory'           => '~2.5.0',
        'zendframework/zend-mime'             => '~2.5.0',
        'zendframework/zend-modulemanager'    => '~2.5.0',
        'zendframework/zend-mvc'              => '~2.5.0',
        'zendframework/zend-navigation'       => '~2.5.0',
        'zendframework/zend-paginator'        => '~2.5.0',
        'zendframework/zend-permissions-acl'  => '~2.5.0',
        'zendframework/zend-permissions-rbac' => '~2.5.0',
        'zendframework/zend-progressbar'      => '~2.5.0',
        'zendframework/zend-serializer'       => '~2.5.0',
        'zendframework/zend-server'           => '~2.5.0',
        'zendframework/zend-servicemanager'   => '~2.5.0',
        'zendframework/zend-session'          => '~2.5.0',
        'zendframework/zend-soap'             => '~2.5.0',
        'zendframework/zend-stdlib'           => '~2.5.0',
        'zendframework/zend-tag'              => '~2.5.0',
        'zendframework/zend-test'             => '~2.5.0',
        'zendframework/zend-text'             => '~2.5.0',
        'zendframework/zend-uri'              => '~2.5.0',
        'zendframework/zend-validator'        => '~2.5.0',
        'zendframework/zend-version'          => '~2.5.0',
        'zendframework/zend-view'             => '~2.5.0',
        'zendframework/zend-xmlrpc'           => '~2.5.0',
        'zendframework/zendxml'               => '~1.0',
    ];

    public const ZF_2_5_2 = [
        'zendframework/zend-authentication'   => '~2.5.0',
        'zendframework/zend-barcode'          => '~2.5.0',
        'zendframework/zend-cache'            => '~2.5.0',
        'zendframework/zend-captcha'          => '~2.5.0',
        'zendframework/zend-code'             => '~2.5.0',
        'zendframework/zend-config'           => '~2.5.0',
        'zendframework/zend-console'          => '~2.5.0',
        'zendframework/zend-crypt'            => '~2.5.0',
        'zendframework/zend-db'               => '~2.5.0',
        'zendframework/zend-debug'            => '~2.5.0',
        'zendframework/zend-di'               => '~2.5.0',
        'zendframework/zend-dom'              => '~2.5.0',
        'zendframework/zend-escaper'          => '~2.5.0',
        'zendframework/zend-eventmanager'     => '~2.5.0',
        'zendframework/zend-feed'             => '~2.5.0',
        'zendframework/zend-file'             => '~2.5.0',
        'zendframework/zend-filter'           => '~2.5.0',
        'zendframework/zend-form'             => '~2.5.0',
        'zendframework/zend-http'             => '~2.5.0',
        'zendframework/zend-i18n'             => '~2.5.0',
        'zendframework/zend-i18n-resources'   => '~2.5.0',
        'zendframework/zend-inputfilter'      => '~2.5.0',
        'zendframework/zend-json'             => '~2.5.0',
        'zendframework/zend-loader'           => '~2.5.0',
        'zendframework/zend-log'              => '~2.5.0',
        'zendframework/zend-mail'             => '~2.5.0',
        'zendframework/zend-math'             => '~2.5.0',
        'zendframework/zend-memory'           => '~2.5.0',
        'zendframework/zend-mime'             => '~2.5.0',
        'zendframework/zend-modulemanager'    => '~2.5.0',
        'zendframework/zend-mvc'              => '~2.5.0',
        'zendframework/zend-navigation'       => '~2.5.0',
        'zendframework/zend-paginator'        => '~2.5.0',
        'zendframework/zend-permissions-acl'  => '~2.5.0',
        'zendframework/zend-permissions-rbac' => '~2.5.0',
        'zendframework/zend-progressbar'      => '~2.5.0',
        'zendframework/zend-serializer'       => '~2.5.0',
        'zendframework/zend-server'           => '~2.5.0',
        'zendframework/zend-servicemanager'   => '~2.5.0',
        'zendframework/zend-session'          => '~2.5.0',
        'zendframework/zend-soap'             => '~2.5.0',
        'zendframework/zend-stdlib'           => '~2.5.0',
        'zendframework/zend-tag'              => '~2.5.0',
        'zendframework/zend-test'             => '~2.5.0',
        'zendframework/zend-text'             => '~2.5.0',
        'zendframework/zend-uri'              => '~2.5.0',
        'zendframework/zend-validator'        => '~2.5.0',
        'zendframework/zend-version'          => '~2.5.0',
        'zendframework/zend-view'             => '~2.5.0',
        'zendframework/zend-xmlrpc'           => '~2.5.0',
        'zendframework/zendxml'               => '^1.0.1',
    ];

    public const ZF_2_5_3 = [
        'zendframework/zend-authentication'   => '^2.5',
        'zendframework/zend-barcode'          => '^2.5',
        'zendframework/zend-cache'            => '^2.5',
        'zendframework/zend-captcha'          => '^2.5',
        'zendframework/zend-code'             => '^2.5',
        'zendframework/zend-config'           => '^2.5',
        'zendframework/zend-console'          => '^2.5',
        'zendframework/zend-crypt'            => '^2.5',
        'zendframework/zend-db'               => '^2.5',
        'zendframework/zend-debug'            => '^2.5',
        'zendframework/zend-di'               => '^2.5',
        'zendframework/zend-dom'              => '^2.5',
        'zendframework/zend-escaper'          => '^2.5',
        'zendframework/zend-eventmanager'     => '^2.5',
        'zendframework/zend-feed'             => '^2.5',
        'zendframework/zend-file'             => '^2.5',
        'zendframework/zend-filter'           => '^2.5',
        'zendframework/zend-form'             => '^2.5',
        'zendframework/zend-http'             => '^2.5',
        'zendframework/zend-i18n'             => '^2.5',
        'zendframework/zend-i18n-resources'   => '^2.5',
        'zendframework/zend-inputfilter'      => '^2.5',
        'zendframework/zend-json'             => '^2.5',
        'zendframework/zend-loader'           => '^2.5',
        'zendframework/zend-log'              => '^2.5',
        'zendframework/zend-mail'             => '^2.5',
        'zendframework/zend-math'             => '^2.5',
        'zendframework/zend-memory'           => '^2.5',
        'zendframework/zend-mime'             => '^2.5',
        'zendframework/zend-modulemanager'    => '^2.5',
        'zendframework/zend-mvc'              => '^2.5',
        'zendframework/zend-navigation'       => '^2.5',
        'zendframework/zend-paginator'        => '^2.5',
        'zendframework/zend-permissions-acl'  => '^2.5',
        'zendframework/zend-permissions-rbac' => '^2.5',
        'zendframework/zend-progressbar'      => '^2.5',
        'zendframework/zend-serializer'       => '^2.5',
        'zendframework/zend-server'           => '^2.5',
        'zendframework/zend-servicemanager'   => '^2.5',
        'zendframework/zend-session'          => '^2.5',
        'zendframework/zend-soap'             => '^2.5',
        'zendframework/zend-stdlib'           => '^2.5',
        'zendframework/zend-tag'              => '^2.5',
        'zendframework/zend-test'             => '^2.5',
        'zendframework/zend-text'             => '^2.5',
        'zendframework/zend-uri'              => '^2.5',
        'zendframework/zend-validator'        => '^2.5',
        'zendframework/zend-version'          => '^2.5',
        'zendframework/zend-view'             => '^2.5',
        'zendframework/zend-xmlrpc'           => '^2.5',
        'zendframework/zendxml'               => '^1.0.1',
    ];

    public const ZF_3_0_0 = [
        'zendframework/zend-authentication'    => '^2.5.3',
        'zendframework/zend-barcode'           => '^2.6',
        'zendframework/zend-cache'             => '^2.7.1',
        'zendframework/zend-captcha'           => '^2.6',
        'zendframework/zend-code'              => '^3.0.2',
        'zendframework/zend-config'            => '^2.6',
        'zendframework/zend-console'           => '^2.6',
        'zendframework/zend-crypt'             => '^3.0',
        'zendframework/zend-db'                => '^2.8.1',
        'zendframework/zend-debug'             => '^2.5.1',
        'zendframework/zend-di'                => '^2.6.1',
        'zendframework/zend-diactoros'         => '^1.3.5',
        'zendframework/zend-dom'               => '^2.6',
        'zendframework/zend-escaper'           => '^2.5.1',
        'zendframework/zend-eventmanager'      => '^3.0.1',
        'zendframework/zend-feed'              => '^2.7',
        'zendframework/zend-file'              => '^2.7',
        'zendframework/zend-filter'            => '^2.7.1',
        'zendframework/zend-form'              => '^2.9',
        'zendframework/zend-http'              => '^2.5.4',
        'zendframework/zend-hydrator'          => '^2.2.1',
        'zendframework/zend-i18n'              => '^2.7.3',
        'zendframework/zend-i18n-resources'    => '^2.5.2',
        'zendframework/zend-inputfilter'       => '^2.7.2',
        'zendframework/zend-json'              => '^3.0',
        'zendframework/zend-json-server'       => '^3.0',
        'zendframework/zend-loader'            => '^2.5.1',
        'zendframework/zend-log'               => '^2.9',
        'zendframework/zend-mail'              => '^2.7.1',
        'zendframework/zend-math'              => '^3.0',
        'zendframework/zend-memory'            => '^2.5.2',
        'zendframework/zend-mime'              => '^2.6',
        'zendframework/zend-modulemanager'     => '^2.7.2',
        'zendframework/zend-mvc'               => '^3.0.1',
        'zendframework/zend-mvc-console'       => '^1.1.9',
        'zendframework/zend-mvc-form'          => '^1.0',
        'zendframework/zend-mvc-i18n'          => '^1.0',
        'zendframework/zend-mvc-plugins'       => '^1.0.1',
        'zendframework/zend-navigation'        => '^2.8.1',
        'zendframework/zend-paginator'         => '^2.7',
        'zendframework/zend-permissions-acl'   => '^2.6',
        'zendframework/zend-permissions-rbac'  => '^2.5.1',
        'zendframework/zend-progressbar'       => '^2.5.2',
        'zendframework/zend-psr7bridge'        => '^0.2.2',
        'zendframework/zend-serializer'        => '^2.8',
        'zendframework/zend-server'            => '^2.7.0',
        'zendframework/zend-servicemanager'    => '^3.1',
        'zendframework/zend-servicemanager-di' => '^1.1',
        'zendframework/zend-session'           => '^2.7.1',
        'zendframework/zend-soap'              => '^2.6',
        'zendframework/zend-stdlib'            => '^3.0.1',
        'zendframework/zend-stratigility'      => '^1.2.1',
        'zendframework/zend-tag'               => '^2.6.1',
        'zendframework/zend-test'              => '^3.0.1',
        'zendframework/zend-text'              => '^2.6',
        'zendframework/zend-uri'               => '^2.5.2',
        'zendframework/zend-validator'         => '^2.8',
        'zendframework/zend-view'              => '^2.8',
        'zendframework/zend-xml2json'          => '^3.0',
        'zendframework/zend-xmlrpc'            => '^2.6',
        'zendframework/zendxml'                => '^1.0.2',
    ];
}
