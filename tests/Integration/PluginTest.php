<?php

namespace Eccube2\Tests\Integration;

/**
 * プラグインのインストールからアンインストールまで。
 *
 * fixtures/plugin/CliTestPlugin を PLUGIN_UPLOAD_REALDIR (data/downloads/plugin/) に置き、
 * plugin:install CliTestPlugin でインストールする。
 *
 * テストはメソッドの定義順に実行される前提で、インストール → 一覧 → 情報 → 無効化 → 有効化 → 更新 → アンインストールの
 * 順に状態を進める。前のテストが終了コードだけで失敗しても後続を確認できるよう、テスト間の依存指定はせず、
 * 各テストは DB の状態を先に確認する。
 */
class PluginTest extends IntegrationTestCase
{
    const CODE = 'CliTestPlugin';

    /** dtb_plugin.enable の値 (EC-CUBE の SC_Initial で定義される PLUGIN_ENABLE_TRUE / PLUGIN_ENABLE_FALSE) */
    const PLUGIN_ENABLE_TRUE = '1';
    const PLUGIN_ENABLE_FALSE = '2';

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

    /**
     * @return array<string, mixed>|null
     */
    private function findPlugin()
    {
        $rows = $this->query('SELECT * FROM dtb_plugin WHERE plugin_code = ?', [self::CODE]);

        return $rows ? $rows[0] : null;
    }

    public function testInstall()
    {
        $this->assertNull($this->findPlugin(), 'テスト開始前に ' . self::CODE . ' がインストールされています');

        $result = $this->eccube(['plugin:install', self::CODE]);

        $this->assertNotNull($this->findPlugin(), 'dtb_plugin に登録されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testList()
    {
        $result = $this->eccube(['plugin:list']);

        $this->assertStringContainsString(self::CODE, $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testInfo()
    {
        $result = $this->eccube(['plugin:info', self::CODE]);

        $this->assertStringContainsString('1.0.0', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testDisable()
    {
        $result = $this->eccube(['plugin:disable', self::CODE]);

        $this->assertSame(self::PLUGIN_ENABLE_FALSE, (string) $this->findPlugin()['enable'], '無効になっていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    /**
     * 無効なプラグインが plugin:list と plugin:info で無効 (x) と表示されること。
     */
    public function testDisabledPluginIsShownAsDisabled()
    {
        $this->assertSame(self::PLUGIN_ENABLE_FALSE, (string) $this->findPlugin()['enable'], '前提: プラグインが無効になっていません');

        $list = $this->eccube(['plugin:list']);
        $this->assertSame(1, preg_match('/^\s*' . self::CODE . '\s.*\s(o|x)\s*$/m', $list->stdout, $m), $list->describe());
        $this->assertSame('x', $m[1], 'plugin:list で無効なプラグインが有効と表示されています' . "\n" . $list->describe());

        $info = $this->eccube(['plugin:info', self::CODE]);
        $this->assertSame(1, preg_match('/^Enable\s*\n-+\s*\n\s*(o|x)\s*$/m', $info->stdout, $m), $info->describe());
        $this->assertSame('x', $m[1], 'plugin:info で無効なプラグインが有効と表示されています' . "\n" . $info->describe());
    }

    public function testEnable()
    {
        $result = $this->eccube(['plugin:enable', self::CODE]);

        $this->assertSame(self::PLUGIN_ENABLE_TRUE, (string) $this->findPlugin()['enable'], '有効になっていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testUpdate()
    {
        $result = $this->eccube(['plugin:update', self::CODE]);

        $this->assertStringContainsString('アップデートしました', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testUninstall()
    {
        $result = $this->eccube(['plugin:uninstall', self::CODE]);

        $this->assertNull($this->findPlugin(), 'dtb_plugin から削除されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testInfoOfUnknownPluginFails()
    {
        $result = $this->eccube(['plugin:info', 'NoSuchPlugin']);

        $this->assertStringContainsString('インストールされていません', $result->output(), $result->describe());
        $this->assertCommandFailed($result);
    }
}
