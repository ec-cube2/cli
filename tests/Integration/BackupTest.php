<?php

namespace Eccube2\Tests\Integration;

/**
 * バックアップの作成 → 一覧 → リストア → 削除。
 *
 * テストはメソッドの定義順に実行される前提で状態を進める (PluginTest と同じ方針)。
 */
class BackupTest extends IntegrationTestCase
{
    /** @var string */
    private static $name;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$name = 'clitest_' . date('YmdHis');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findBackup()
    {
        $rows = $this->query('SELECT * FROM dtb_bkup WHERE bkup_name = ?', [self::$name]);

        return $rows ? $rows[0] : null;
    }

    public function testCreate()
    {
        $result = $this->eccube(['backup:create', self::$name, 'CLI テスト']);

        $this->assertNotNull($this->findBackup(), 'dtb_bkup に登録されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testList()
    {
        $result = $this->eccube(['backup:list']);

        $this->assertStringContainsString(self::$name, $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testRestore()
    {
        $result = $this->eccube(['backup:restore', self::$name, '--yes']);

        $this->assertStringContainsString('リストアを完了しました', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testDelete()
    {
        $result = $this->eccube(['backup:delete', self::$name, '--yes']);

        $this->assertNull($this->findBackup(), 'dtb_bkup から削除されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    /**
     * --yes なしで確認に「いいえ」と答えたら何もしないこと。
     */
    public function testDeleteCanBeCancelled()
    {
        $result = $this->eccube(['backup:delete', 'no-such-backup'], "no\n");

        $this->assertStringNotContainsString('バックアップを削除しました', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }
}
