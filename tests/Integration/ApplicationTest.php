<?php

namespace Eccube2\Tests\Integration;

/**
 * EC-CUBE に組み込んだ状態で Application が起動すること。
 */
class ApplicationTest extends IntegrationTestCase
{
    public function testListCommandShowsAllCommands()
    {
        $result = $this->eccube(['list']);

        $this->assertStringContainsString('plugin:install', $result->stdout, $result->describe());
        $this->assertStringContainsString('member:create', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    /**
     * Application::appendConfigPath() で外部パッケージが追加したコマンドも登録されること。
     *
     * EC-CUBE 2.25 の require-dev にある nanasess/eccube2-fixture-generator は
     * autoload の files で appendConfigPath() を呼び、eccube:fixtures:generate を追加する。
     */
    public function testCommandsAddedByAppendConfigPathAreRegistered()
    {
        if (!is_dir(self::$eccubeRoot . '/data/vendor/nanasess/eccube2-fixture-generator')) {
            $this->markTestSkipped('nanasess/eccube2-fixture-generator がインストールされていません');
        }

        $result = $this->eccube(['list', 'eccube']);

        $this->assertStringContainsString('eccube:fixtures:generate', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testHelpCommand()
    {
        $result = $this->eccube(['help', 'plugin:install']);

        $this->assertStringContainsString('プラグインコード', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }
}
