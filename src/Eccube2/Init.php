<?php

namespace Eccube2;

class Init
{
    protected static $init = false;

    public function __construct()
    {
    }

    /**
     * @param string|null $htmlDir
     * @return Init
     * @throws \Exception
     */
    public static function init($htmlDir = null)
    {
        if (static::$init) {
            return new self;
        }

        if ($htmlDir === null) {
            // EC-CUBE CLI が vendor以下にインストールされているかチェック
            if (basename(realpath(__DIR__ . '/../../../..')) === 'vendor') {
                $vendorDir = realpath(__DIR__ . '/../../../..');

                if (basename(realpath($vendorDir . '/..')) === 'data') {
                    $baseDir = realpath($vendorDir . '/../..');
                } else {
                    $baseDir = realpath($vendorDir . '/..');
                }
            } else {
                throw new \Exception('vendor が推測できません.指定してください.');
            }

            // htmlディレクトリあり
            if (file_exists($baseDir . '/html/require.php')) {
                $htmlDir = $baseDir . '/html';
            }
            // htmlディレクトリなし
            elseif (file_exists($baseDir . '/require.php')) {
                $htmlDir = $baseDir;
            }
        }

        if (!file_exists($htmlDir . '/require.php')) {
            throw new \Exception('HTML_REALDIR が推測できません.指定してください.');
        }

        require_once $htmlDir . '/require.php';
        static::setErrorHandler();
        static::$init = true;

        return new self;
    }

    public static function setErrorHandler()
    {
        set_error_handler(
            function ($errno, $errstr, $errfile, $errline) {
                if ($errno === E_ERROR || $errno === E_USER_ERROR) {
                    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
                }
            }
        );
    }
}
