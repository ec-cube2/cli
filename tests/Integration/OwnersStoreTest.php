<?php

namespace Eccube2\Tests\Integration;

/**
 * オーナーズストアとモジュールのうち、外部と通信しないコマンド。
 *
 * ownersstore:list と module:install / module:update はオーナーズストアと通信するため、CI では実行しない。
 */
class OwnersStoreTest extends IntegrationTestCase
{
    public function testSetKey()
    {
        $key = 'clitest' . substr(md5(uniqid('', true)), 0, 8);
        $result = $this->eccube(['ownersstore:set-key', $key]);

        $rows = $this->query('SELECT public_key FROM dtb_ownersstore_settings');
        $this->assertNotEmpty($rows, 'dtb_ownersstore_settings に登録されていません' . "\n" . $result->describe());
        $this->assertSame($key, $rows[0]['public_key'], $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testModuleList()
    {
        $result = $this->eccube(['module:list']);

        $this->assertStringContainsString('モジュール一覧', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testModuleInfoOfUnknownModuleFails()
    {
        $result = $this->eccube(['module:info', 'NoSuchModule']);

        $this->assertCommandFailed($result);
    }
}
