<?php

namespace Eccube2\Tests\Integration;

/**
 * 郵便番号データ。
 *
 * zip:download と zip:update は日本郵便のサイトから CSV を取得するため、CI では実行しない。
 */
class ZipTest extends IntegrationTestCase
{
    public function testInfo()
    {
        $result = $this->eccube(['zip:info']);

        $this->assertStringContainsString('郵便番号DB', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testDelete()
    {
        $result = $this->eccube(['zip:delete', '--yes']);

        $rows = $this->query('SELECT COUNT(*) AS cnt FROM mtb_zip');
        $this->assertSame('0', (string) $rows[0]['cnt'], 'mtb_zip が空になっていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }
}
