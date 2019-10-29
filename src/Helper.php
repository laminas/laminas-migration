<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

use Laminas\ZendFrameworkBridge\RewriteRules;

class Helper
{
    /**
     * @return string[]
     */
    private static function replacements()
    {
        return RewriteRules::namespaceRewrite() + array(
            // Do not rewrite:
            'ZF\Console' => 'ZF\Console',
            'zfcampus/zf-console' => 'zfcampus/zf-console',
            'Zend\Version' => 'Zend\Version',
            'zendframework/zend-version' => 'zendframework/zend-version',
            'ZendPdf' => 'ZendPdf',
            'zendframework/zendpdf' => 'zendframework/zendpdf',
            'zf-commons' => 'zf-commons',
            'api-skeletons/zf-' => 'api-skeletons/zf-',
            'phpro/zf-' => 'phpro/zf-',

            // Packages rewrite rules:
            'zenddiagnostics' => 'laminas-diagnostics',
            'zendoauth' => 'laminas-oauth',
            'zendservice-apple-apns' => 'laminas-apple-apns',
            'zendservice-google-gcm' => 'laminas-google-gcm',
            'zendservice-recaptcha' => 'laminas-recaptcha',
            'zendservice-twitter' => 'laminas-twitter',
            'zendxml' => 'laminas-xml',
            'zendframework/zend-problem-details' => 'expressive/expressive-problem-details',
            'zfcampus/zf-composer-autoloading' => 'laminas/laminas-composer-autoloading',
            'zfcampus/zf-deploy' => 'laminas/laminas-deploy',
            'zfcampus/zf-development-mode' => 'laminas/laminas-development-mode',

            // Additional rules - Config/Names
            'Zend' => 'Laminas',
            'zendframework' => 'laminas',
            'zend-expressive' => 'expressive',
            'zend_expressive' => 'expressive',
            'zend' => 'laminas',
            'zf-apigility' => 'apigility',
            'zf_apigility' => 'apigility',
            'zf-' => 'apigility-',
            'zf_' => 'apigility_',
            'zfcampus' => 'apigility',
        );
    }

    /**
     * @param string $string
     * @return string
     */
    public static function replace($string)
    {
        return strtr($string, self::replacements());
    }

    /**
     * @param string $file
     * @return false|int
     */
    public static function writeJson($file, array $data)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        return file_put_contents($file, $content);
    }
}
