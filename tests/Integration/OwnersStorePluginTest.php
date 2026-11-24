<?php

namespace Eccube2\Tests\Integration;

/**
 * プラグインが HTTP/Request.php を require_once する環境で、オーナーズストアのコマンドが動くこと。
 *
 * EC-CUBE の SC_ClassAutoloader は、クラスのファイルを include する前にプラグインを初めてロードする。
 * mdl_paypal_express のように、プラグインが同じファイルを require_once すると、続く include で
 * "Cannot declare class HTTP_Request" の Fatal error になっていた。
 *
 * fixtures/plugin/CliTestRequirePlugin をインストールして有効にし、OSTORE_URL を接続できないアドレスに変えて
 * ownersstore:list を実行する。オーナーズストアとは通信せず、HTTP_Request の送信で失敗するところまでを確かめる。
 */
class OwnersStorePluginTest extends IntegrationTestCase
{
    const CODE = 'CliTestRequirePlugin';

    /** 接続を拒否されるアドレス (discard ポート) */
    const UNREACHABLE_URL = '"http://127.0.0.1:9/"';

    /** @var string|null */
    private static $originalUrl;

    /** @var string|null */
    private static $originalKey;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::removeDir(self::pluginDir());
        self::copyDir(__DIR__ . '/fixtures/plugin/' . self::CODE, self::pluginDir());
    }

    public static function tearDownAfterClass(): void
    {
        self::removeDir(self::pluginDir());
    }

    private static function pluginDir(): string
    {
        return self::$eccubeRoot . '/data/downloads/plugin/' . self::CODE;
    }

    protected function setUp(): void
    {
        // プラグインはインストール直後は無効なので、有効にしてロードされるようにする
        $this->assertCommandSucceeded($this->eccube(['plugin:install', self::CODE]));
        $this->assertCommandSucceeded($this->eccube(['plugin:enable', self::CODE]));

        $rows = $this->query('SELECT name FROM mtb_constants WHERE id = ?', ['OSTORE_URL']);
        $this->assertNotEmpty($rows, 'OSTORE_URL が mtb_constants にありません');
        self::$originalUrl = $rows[0]['name'];
        $this->assertCommandSucceeded($this->eccube(['parameter:set', 'OSTORE_URL', self::UNREACHABLE_URL]));

        // 認証キーが無いと、HTTP_Request を使う前に失敗する
        $rows = $this->query('SELECT public_key FROM dtb_ownersstore_settings');
        self::$originalKey = $rows ? (string) $rows[0]['public_key'] : '';
        $this->assertCommandSucceeded($this->eccube(['ownersstore:set-key', 'clitest' . substr(md5(uniqid('', true)), 0, 8)]));
    }

    protected function tearDown(): void
    {
        if (self::$originalUrl !== null) {
            $this->eccube(['parameter:set', 'OSTORE_URL', self::$originalUrl]);
        }
        if (self::$originalKey !== null && self::$originalKey !== '') {
            $this->eccube(['ownersstore:set-key', self::$originalKey]);
        }
        $this->eccube(['plugin:uninstall', self::CODE]);
    }

    public function testListDoesNotRedeclareHttpRequest()
    {
        $result = $this->eccube(['ownersstore:list']);

        // PHP 7 系は "Cannot declare class HTTP_Request"、PHP 8 系は "Cannot redeclare class HTTP_Request"
        $this->assertStringNotContainsString('declare class HTTP_Request', $result->output(), $result->describe());
        $this->assertStringNotContainsString('Fatal error', $result->output(), $result->describe());
        // 接続できないので、HTTP リクエストのエラーで失敗する
        $this->assertCommandFailed($result);
    }
}
