<?php

namespace Eccube2\Tests\Integration;

class CacheTest extends IntegrationTestCase
{
    public function testClear()
    {
        $start = self::startClock();
        $result = $this->eccube(['cache:clear']);

        $this->assertStringContainsString('キャッシュをクリアしました', $result->stdout, $result->describe());
        $this->assertFileExists(self::$eccubeRoot . '/data/cache/mtb_constants.php', 'パラメーターキャッシュが再生成されていません');
        $this->assertCacheCleared($start, $result);
        $this->assertCommandSucceeded($result);
    }

    public function testClearWithoutWarmup()
    {
        $start = self::startClock();
        $result = $this->eccube(['cache:clear', '--no-warmup']);

        $this->assertStringContainsString('キャッシュをクリアしました', $result->stdout, $result->describe());
        $this->assertFalse(is_file(self::$eccubeRoot . '/data/cache/mtb_constants.php'), 'パラメーターキャッシュが削除されていません');
        $this->assertCacheCleared($start, $result);
        $this->assertCommandSucceeded($result);

        // 後続のテストのためにキャッシュを戻す
        $this->eccube(['cache:clear']);
    }

    /**
     * コマンドを実行する前の時刻。ファイルの更新日時 (秒単位) と比べるので、秒が変わるのを待ってから返す。
     */
    private static function startClock(): int
    {
        $start = time() + 1;
        while (time() < $start) {
            usleep(100000);
        }

        return $start;
    }

    /**
     * コマンドを実行する前からあったマスターデータのキャッシュ (data/cache/mtb_*.serial) と、
     * Smarty のコンパイル済みファイルが残っていない。
     *
     * マスターデータのキャッシュは、消した後に同じプロセスの中で作り直されるものがあるので、更新日時で見分ける。
     * CLI は E_WARNING を表示しないので、消せなくてもコマンドは成功する。そのため結果のファイルで確かめる。
     */
    private function assertCacheCleared(int $start, CommandResult $result)
    {
        $root = self::$eccubeRoot;
        $this->assertSame([], self::filesOlderThan($start, glob($root . '/data/cache/mtb_*.serial')), 'マスターデータのキャッシュが残っています' . "\n" . $result->describe());

        // コンパイル済みファイルは templates_c/<テンプレートのコード>/ の中にできる。
        // 直下のファイル (.gitkeep や、2.13 が同梱している dummy) は数えない
        $templatesC = $root . '/data/Smarty/templates_c';
        $compiled = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templatesC, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile() && $file->getPath() !== $templatesC) {
                $compiled[] = $file->getPathname();
            }
        }
        $this->assertSame([], self::filesOlderThan($start, $compiled), 'テンプレートのキャッシュが残っています' . "\n" . $result->describe());
    }

    /**
     * @param string[] $files
     * @return string[] 更新日時が $start より前のファイル (ECCUBE_ROOT からの相対パス)
     */
    private static function filesOlderThan(int $start, array $files): array
    {
        clearstatcache();
        $old = [];
        foreach ($files as $file) {
            if (filemtime($file) < $start) {
                $old[] = substr($file, strlen(self::$eccubeRoot) + 1);
            }
        }

        return $old;
    }
}
