<?php

namespace Eccube2\Tests\Integration;

class CacheTest extends IntegrationTestCase
{
    public function testClear()
    {
        $result = $this->eccube(['cache:clear']);

        $this->assertStringContainsString('キャッシュをクリアしました', $result->stdout, $result->describe());
        $this->assertFileExists(self::$eccubeRoot . '/data/cache/mtb_constants.php', 'パラメーターキャッシュが再生成されていません');
        $this->assertCommandSucceeded($result);
    }

    public function testClearWithoutWarmup()
    {
        $result = $this->eccube(['cache:clear', '--no-warmup']);

        $this->assertStringContainsString('キャッシュをクリアしました', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);

        // 後続のテストのためにキャッシュを戻す
        $this->eccube(['cache:clear']);
    }
}
