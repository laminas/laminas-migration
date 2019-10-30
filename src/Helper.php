<?php

/**
 * @see       https://github.com/laminas/laminas-migration for the canonical source repository
 * @copyright https://github.com/laminas/laminas-migration/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-migration/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Migration;

class Helper
{
    /**
     * @return string[]
     */
    private static function replacements()
    {
        return [
            // Package namespaces.
            // Duplicates with varying numbers of escapes provided to ensure
            // matching occurs properly across all edge cases.
            'Zend\\ProblemDetails' => 'Expressive\\ProblemDetails',
            'Zend\\\\ProblemDetails' => 'Expressive\\\\ProblemDetails',
            'Zend\\Expressive' => 'Expressive',
            'Zend\\\\Expressive' => 'Expressive',
            'ZF\\ComposerAutoloading' => 'Laminas\\ComposerAutoloading',
            'ZF\\\\ComposerAutoloading' => 'Laminas\\\\ComposerAutoloading',
            'ZF\\Deploy' => 'Laminas\\Deploy',
            'ZF\\\\Deploy' => 'Laminas\\\\Deploy',
            'ZF\\DevelopmentMode' => 'Laminas\\DevelopmentMode',
            'ZF\\\\DevelopmentMode' => 'Laminas\\\\DevelopmentMode',
            'ZF\\Apigility' => 'Apigility',
            'ZF\\\\Apigility' => 'Apigility',
            'ZendXml' => 'Laminas\\Xml',
            'ZendOAuth' => 'Laminas\\OAuth',
            'ZendDiagnostics' => 'Laminas\\Diagnostics',
            'ZendService\\ReCaptcha' => 'Laminas\\ReCaptcha',
            'ZendService\\\\ReCaptcha' => 'Laminas\\\\ReCaptcha',
            'ZendService\\Twitter' => 'Laminas\\Twitter',
            'ZendService\\\\Twitter' => 'Laminas\\\\Twitter',

            // Do not rewrite:
            'Doctrine\Zend' => 'Doctrine\Zend',
            'zfcampus/zf-console' => 'zfcampus/zf-console',
            'Zend\Version' => 'Zend\Version',
            'zendframework/zend-version' => 'zendframework/zend-version',
            'ZendPdf' => 'ZendPdf',
            'zendframework/zendpdf' => 'zendframework/zendpdf',
            'zf-commons' => 'zf-commons',
            'api-skeletons/zf-' => 'api-skeletons/zf-',
            'phpro/zf-' => 'phpro/zf-',
            'doctrine-zend' => 'doctrine-zend',
            'ZendService' => 'ZendService',
            'ZF\Console' => 'ZF\Console',

            // Packages rewrite rules:
            'zenddiagnostics' => 'laminas-diagnostics',
            'zendoauth' => 'laminas-oauth',
            'zendservice-recaptcha' => 'laminas-recaptcha',
            'zendservice-twitter' => 'laminas-twitter',
            'zendxml' => 'laminas-xml',
            'zendframework/zend-problem-details' => 'expressive/expressive-problem-details',
            'zendframework/zend-expressive' => 'expressive/expressive',
            'zfcampus/apigility-documentation' => 'apigility/documentation',
            'zfcampus/zf-composer-autoloading' => 'laminas/laminas-composer-autoloading',
            'zfcampus/zf-deploy' => 'laminas/laminas-deploy',
            'zfcampus/zf-development-mode' => 'laminas/laminas-development-mode',
            'zend-problem-details' => 'expressive-problem-details',
            'zf-composer-autoloading' => 'laminas-composer-autoloading',
            'zf-deploy' => 'laminas-deploy',
            'zf-development-mode' => 'laminas-development-mode',

            // Additional rules - Config/Names
            'Zend' => 'Laminas',
            'ZF\\' => 'Apigility\\',
            'zendframework' => 'laminas',
            'zend-expressive' => 'expressive',
            'zend_expressive' => 'expressive',
            'zend' => 'laminas',
            'zf-apigility' => 'apigility',
            'zf_apigility' => 'apigility',
            'zf-' => 'apigility-',
            'zf_' => 'apigility_',
            'zfcampus' => 'apigility',
        ];
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
