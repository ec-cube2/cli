<?php

namespace Eccube2\Tests\Structure;

use Eccube2\Console\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * EC-CUBE 本体なしで確認できる範囲 (DI コンテナの組み立てとコマンドの登録) をテストする。
 *
 * Application のコンストラクタは define('SAFE', true) を実行し、静的プロパティ $configPaths にも追記するため、
 * 1 プロセスで 1 回しか生成できない。テストクラス全体で 1 つのインスタンスを共有する。
 */
class ApplicationTest extends TestCase
{
    /** README の Usage に載っているコマンド (Symfony 組み込みのコマンドを除く) */
    const EXPECTED_COMMANDS = [
        'info',
        'install',
        'backup:create',
        'backup:delete',
        'backup:list',
        'backup:restore',
        'cache:clear',
        'install:agreement',
        'install:config',
        'install:copy-image',
        'install:create-sequence',
        'install:create-table',
        'install:drop-sequence',
        'install:drop-table',
        'install:insert-data',
        'install:send-info',
        'install:set-base-info',
        'member:create',
        'member:delete',
        'member:disable',
        'member:enable',
        'member:set-password',
        'module:info',
        'module:install',
        'module:list',
        'module:uninstall',
        'module:update',
        'ownersstore:download',
        'ownersstore:list',
        'ownersstore:set-key',
        'parameter:get',
        'parameter:set',
        'plugin:disable',
        'plugin:enable',
        'plugin:info',
        'plugin:install',
        'plugin:list',
        'plugin:uninstall',
        'plugin:update',
        'template:mobile:get',
        'template:mobile:set',
        'template:pc:get',
        'template:pc:set',
        'template:smartphone:get',
        'template:smartphone:set',
        'util:random-string',
        'zip:delete',
        'zip:download',
        'zip:info',
        'zip:update',
    ];

    /** Symfony Console が組み込みで持つコマンド (バージョンによって有無が異なる) */
    const BUILTIN_COMMANDS = ['help', 'list', 'completion', '_complete'];

    /** @var Application|null */
    private static $app;

    /** @var \Throwable|null */
    private static $bootError;

    public static function setUpBeforeClass(): void
    {
        try {
            self::$app = new Application();
            self::$app->setAutoExit(false);
        } catch (\Throwable $e) {
            self::$bootError = $e;
        }
    }

    private function app(): Application
    {
        if (self::$bootError !== null) {
            $this->fail('Application を生成できません: ' . get_class(self::$bootError) . ': ' . self::$bootError->getMessage());
        }

        return self::$app;
    }

    public function testApplicationCanBeCreated()
    {
        $this->assertInstanceOf(Application::class, $this->app());
    }

    public function testAllCommandsAreRegistered()
    {
        $names = array_values(array_diff(array_keys($this->app()->all()), self::BUILTIN_COMMANDS));
        sort($names);
        $expected = self::EXPECTED_COMMANDS;
        sort($expected);

        $this->assertSame($expected, $names);
    }

    /**
     * コマンドローダーからコマンドを生成できること (コンストラクタの autowire と configure() が通ること)。
     *
     * @dataProvider commandNameProvider
     */
    public function testCommandCanBeInstantiated(string $name)
    {
        $command = $this->app()->find($name);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertSame($name, $command->getName());
        $this->assertNotEmpty($command->getDescription(), $name . ' に説明文がありません');
    }

    public function commandNameProvider(): array
    {
        $data = [];
        foreach (self::EXPECTED_COMMANDS as $name) {
            $data[$name] = [$name];
        }

        return $data;
    }

    public function testListCommandRuns()
    {
        $tester = new ApplicationTester($this->app());
        $exitCode = $tester->run(['command' => 'list']);

        $this->assertSame(0, $exitCode, $tester->getDisplay());
        $this->assertStringContainsString('plugin:install', $tester->getDisplay());
    }

    /**
     * EC-CUBE 本体が読み込まれていない状態で EC-CUBE を必要とするコマンドを実行したとき、
     * PHP の Fatal error ではなく例外として失敗すること。
     */
    public function testCommandFailsWithExceptionWithoutEccube()
    {
        $tester = new ApplicationTester($this->app());
        $exitCode = $tester->run(['command' => 'info']);

        $this->assertNotSame(0, $exitCode);
        $this->assertStringContainsString('vendor が推測できません', $tester->getDisplay());
    }
}
