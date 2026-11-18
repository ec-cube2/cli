<?php

namespace Eccube2\Tests\Integration;

/**
 * インストール関連のコマンド。DB を作り直すので、結合テストの最後に実行する (phpunit.xml.dist の順序を参照)。
 *
 * テストはメソッドの定義順に実行される前提で、
 * 個別コマンドでの削除 → 作成 → 再度削除 → install コマンドでの一括インストールの順に状態を進める。
 * 各テストの前提条件 (テーブルが無い状態、空のテーブルがある状態、初期データがある状態) は、
 * ほかのコマンドの成否に左右されないよう、EC-CUBE の SQL ファイルを直接実行して用意する。
 *
 * install:send-info と、install の「インストール情報送信」は ec-cube.net に送信するため実行しない
 * (install は --no-send-info を付けて実行する)。
 */
class InstallTest extends IntegrationTestCase
{
    /**
     * テーブルが存在すれば true。
     */
    private function tableExists(string $table): bool
    {
        try {
            $this->query('SELECT COUNT(*) AS cnt FROM ' . $table);

            return true;
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * CLI を使わずにテーブルを削除する。
     *
     * 途中まで作られたテーブルも消せるよう、常に drop_table.sql (すべて DROP TABLE IF EXISTS) を実行する。
     */
    private function dropAllTables()
    {
        $this->executeSqlFile(self::$eccubeRoot . '/html/install/sql/drop_table.sql');
        $this->assertFalse($this->tableExists('dtb_baseinfo'), '前提: テーブルを削除できません');
    }

    /**
     * CLI を使わずに空のテーブルを作り直す。
     */
    private function recreateEmptyTables()
    {
        $this->dropAllTables();
        $this->executeSqlFile(self::$eccubeRoot . '/html/install/sql/create_table_' . $this->dbType() . '.sql');
        $this->assertTrue($this->tableExists('dtb_baseinfo'), '前提: テーブルを作成できません');
    }

    /**
     * CLI を使わずに、テーブルと初期データがある状態にする。
     */
    private function ensureInitialData()
    {
        if ($this->tableExists('mtb_constants') && $this->query('SELECT COUNT(*) AS cnt FROM mtb_constants')[0]['cnt'] > 0) {
            return;
        }
        $this->recreateEmptyTables();
        $this->executeSqlFile(self::$eccubeRoot . '/html/install/sql/insert_data.sql');
    }

    private function dbType(): string
    {
        $config = file_get_contents(self::$eccubeRoot . '/data/config/config.php');
        $this->assertSame(1, preg_match("/define\\('DB_TYPE', '([a-z]+)'\\)/", $config, $m), 'config.php から DB_TYPE を読めません');

        return $m[1];
    }

    public function testAgreement()
    {
        $result = $this->eccube(['install:agreement']);

        $this->assertStringContainsString('EC-CUBE', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testConfig()
    {
        // data/config/config.php は eccube_install.sh で作成済み。install:config 自体は設定を変更しない
        $result = $this->eccube(['install:config']);

        $this->assertCommandSucceeded($result);
    }

    public function testDropSequenceAndTable()
    {
        $dropSequence = $this->eccube(['install:drop-sequence', '--yes']);
        $dropTable = $this->eccube(['install:drop-table', '--yes']);

        $this->assertFalse($this->tableExists('dtb_baseinfo'), 'テーブルが削除されていません' . "\n" . $dropTable->describe());
        $this->assertCommandSucceeded($dropSequence);
        $this->assertCommandSucceeded($dropTable);
    }

    public function testCreateTable()
    {
        $this->dropAllTables();

        $result = $this->eccube(['install:create-table', '--yes']);

        $this->assertTrue($this->tableExists('dtb_baseinfo'), 'テーブルが作成されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testInsertData()
    {
        $this->recreateEmptyTables();

        $result = $this->eccube(['install:insert-data', '--yes']);

        $rows = $this->query("SELECT COUNT(*) AS cnt FROM mtb_constants");
        $this->assertGreaterThan(0, (int) $rows[0]['cnt'], '初期データが投入されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testCreateSequence()
    {
        $this->ensureInitialData();

        $result = $this->eccube(['install:create-sequence', '--yes']);

        $this->assertStringContainsString('シーケンス', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testCopyImage()
    {
        $result = $this->eccube(['install:copy-image']);

        $this->assertDirectoryExists(self::$eccubeRoot . '/html/upload/save_image', $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testSetBaseInfo()
    {
        $this->ensureInitialData();

        $result = $this->eccube(['install:set-base-info', 'CLI テスト SHOP', 'cli-test@example.com']);

        $rows = $this->query('SELECT shop_name, email01 FROM dtb_baseinfo');
        $this->assertNotEmpty($rows, 'dtb_baseinfo が空です' . "\n" . $result->describe());
        $this->assertSame('CLI テスト SHOP', $rows[0]['shop_name'], $result->describe());
        $this->assertSame('cli-test@example.com', $rows[0]['email01'], $result->describe());
        $this->assertCommandSucceeded($result);
    }

    /**
     * install コマンドで一括インストールできること (既存のテーブルを削除してから実行する)。
     */
    public function testInstall()
    {
        $this->dropAllTables();

        $result = $this->eccube(['install', '--yes', '--no-send-info', 'CLI インストール SHOP', 'cli-install@example.com']);

        $this->assertTrue($this->tableExists('dtb_baseinfo'), 'テーブルが作成されていません' . "\n" . $result->describe());
        $rows = $this->query('SELECT shop_name FROM dtb_baseinfo');
        $this->assertNotEmpty($rows, 'dtb_baseinfo が空です' . "\n" . $result->describe());
        $this->assertSame('CLI インストール SHOP', $rows[0]['shop_name'], $result->describe());
        $this->assertStringContainsString('インストールが完了しました', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }
}
