<?php

namespace Eccube2\Tests\Integration;

/**
 * Web サーバーの書き込み権限を絞った環境 (.github/docker/restricted/、ec-cube2/cli#20) の前提と、
 * どのコマンドにも共通する問題を確かめる。ECCUBE_RESTRICTED_PERMISSIONS=1 のときだけ実行する。
 *
 * CLI を実行する前の状態を確かめるので、結合テストの最初に実行する。
 */
class RestrictedPermissionsTest extends IntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        if (!self::isRestrictedPermissions()) {
            self::markTestSkipped('ECCUBE_RESTRICTED_PERMISSIONS=1 のときだけ実行します');
        }
    }

    /**
     * このクラスは環境の前提を確かめるので、失敗を未完了に置き換えない (IntegrationTestCase::onNotSuccessfulTest())。
     */
    protected function onNotSuccessfulTest(\Throwable $t): void
    {
        throw $t;
    }

    /**
     * 権限の検査そのものが正しく動くための前提:restrict-permissions.sh のとおりに所有者と mode が揃っている。
     */
    public function testPermissionsAreRestricted()
    {
        $siteUid = (int) getenv('ECCUBE_SITE_UID');
        $this->assertGreaterThan(0, $siteUid, 'ECCUBE_SITE_UID が設定されていません');
        $this->assertNotEmpty(self::writableDirs(), 'ECCUBE_WRITABLE_DIRS が設定されていません');

        $this->assertSame($siteUid, fileowner(self::$eccubeRoot . '/data'));
        foreach (self::writableDirs() as $dir) {
            $path = self::$eccubeRoot . '/' . $dir;
            $this->assertDirectoryExists($path);
            $this->assertNotSame($siteUid, fileowner($path), $dir . ' が Web サーバーの所有になっていません');
            $this->assertSame('755', sprintf('%o', fileperms($path) & 0777), $dir . ' の mode');
            $this->assertFalse(is_writable($path), $dir . ' に SSH ユーザーが書き込めます');
        }

        $this->assertSame([], self::findOwnershipProblems());
    }

    /**
     * CLI を実行する前に、Web サーバーでトップページを表示できる (以降のテストで、CLI が壊したかを判定する基準)。
     */
    public function testTopPage()
    {
        $this->assertNull(self::findWebProblem());
    }

    /**
     * SSH ユーザーは data/logs/ (Web サーバーの所有) にログを書けない。
     *
     * GC_Utils::gfPrintLog() は error_log() で追記するので、処理は止まらないが、ログが残らない。
     * 警告は EC-CUBE のエラーハンドラーが標準出力に出す (CLI では Init::setErrorHandler() が握りつぶすので何も出ない)。
     * 扱いが決まるまでは未完了として記録する。
     */
    public function testLogging()
    {
        $message = 'restricted-permissions ' . uniqid();
        $result = $this->runEccubePhp('GC_Utils_Ex::gfPrintLog(' . var_export($message, true) . ');');

        $this->assertSame(0, $result[0], $result[1] . $result[2]);
        if (preg_match('/^.*data\/logs\/.*$/m', $result[1] . $result[2], $m)) {
            $this->markTestIncomplete('SSH ユーザーは data/logs/ にログを書けない: ' . trim(strip_tags($m[0])));
        }
        $this->assertStringContainsString($message, (string) file_get_contents(self::$eccubeRoot . '/data/logs/site.log'));
    }
}
