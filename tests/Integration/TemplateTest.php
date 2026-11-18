<?php

namespace Eccube2\Tests\Integration;

/**
 * テンプレートの取得と設定。
 *
 * 設定は現在の値をそのまま設定し直す (別のテンプレートに切り替えるとテンプレートファイルの差し替えが走るため)。
 */
class TemplateTest extends IntegrationTestCase
{
    public function deviceProvider(): array
    {
        return [
            'pc' => ['pc'],
            'smartphone' => ['smartphone'],
            'mobile' => ['mobile'],
        ];
    }

    /**
     * @dataProvider deviceProvider
     */
    public function testGetAndSet(string $device)
    {
        $get = $this->eccube(['template:' . $device . ':get']);

        // 出力は "    <テンプレートコード>" の 1 行 (PHP の Deprecated などが混ざる場合があるので行で探す)
        $this->assertSame(1, preg_match('/^    (\S+)$/m', $get->stdout, $m), $get->describe());
        $code = $m[1];

        $set = $this->eccube(['template:' . $device . ':set', $code]);
        $this->assertStringContainsString('設定しました', $set->stdout, $set->describe());

        $this->assertCommandSucceeded($get);
        $this->assertCommandSucceeded($set);
    }
}
