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

        // EC-CUBE 本家 master の dtb_migration は migration_id を SERIAL で定義している。PostgreSQL では
        // SERIAL のシーケンスを DROP できないので、EC-CUBE 本体のリストア (LC_Page_Admin_System_Bkup::lfRestore()
        // → SC_Query::setVal()) がシーケンスを作り直すところで失敗する。CLI の問題ではないので、本家で直るまでは
        // この失敗に限って未完了として扱う (直れば自動的に通常のテストに戻る)。EC-CUBE/ec-cube2#1484
        if ($result->exitCode !== 0
            && preg_match('/dropSequence\s+\[dtb_migration_migration_id\]/', $result->output())) {
            $this->markTestIncomplete(
                'EC-CUBE 本体のリストアが SERIAL のシーケンス dtb_migration_migration_id を作り直せない (PostgreSQL)'
                . "\n" . $result->describe()
            );
        }

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
