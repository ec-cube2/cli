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
                throw new \Exception('vendor が推測できません。 $htmlDir を指定してください。');
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
            throw new \Exception('require.php が見つかりません。 $htmlDir を設定してください。');
        }

        require_once $htmlDir . '/require.php';
        static::setErrorHandler();
        static::$init = true;

        return new self;
    }

    /**
     * data/class_extends/ 以下の _Ex ファイルを、存在する場合だけ読み込む。
     *
     * EC-CUBE 2.25 で page_extends/ 以下のほとんどの _Ex ファイルが削除され、
     * 代わりにオートローダーが _Ex クラスを用意するようになった (EC-CUBE/ec-cube2#526)。
     * 2.17 以前では従来どおりファイルを読み込む。
     *
     * @param string $path CLASS_EX_REALDIR からの相対パス
     */
    public static function requireClassEx($path)
    {
        $file = CLASS_EX_REALDIR . $path;
        if (file_exists($file)) {
            require_once $file;
        }
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
