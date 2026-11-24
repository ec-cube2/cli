<?php

namespace Eccube2\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * EC-CUBE 2 に組み込んだ CLI を別プロセスで実行するテストの基底クラス。
 *
 * 前提:
 * - 環境変数 ECCUBE_ROOT に EC-CUBE のルートディレクトリ (data/ と html/ がある場所) を指定する。
 * - CLI は ECCUBE_ROOT/data/vendor/ec-cube2/cli (2.17 以降) か ECCUBE_ROOT/vendor/ec-cube2/cli (2.13) に、
 *   シンボリックリンクではなく実体としてインストールされている (Init::init() は自身の設置場所から vendor ディレクトリを推定するため)。
 * - 環境変数 ECCUBE_PHP を指定すると、CLI と EC-CUBE はそのコマンドで実行する (既定はテストを実行している PHP)。
 *   テストは PHPUnit 8.5 / 9.6 が動く PHP で実行し、CLI だけを古い PHP で動かすために使う。
 * - DB は EC-CUBE の eccube_install.sh で初期化済み。
 *
 * セットアップは .github/scripts/setup-eccube.sh を参照。
 *
 * EC-CUBE はグローバルな定数と状態を持つため、同じプロセスでは 1 回しか初期化できない。
 * そのためコマンドは毎回別プロセスで実行し、終了コードと出力で結果を確認する。
 */
abstract class IntegrationTestCase extends TestCase
{
    /** @var string */
    protected static $eccubeRoot;

    public static function setUpBeforeClass(): void
    {
        $root = getenv('ECCUBE_ROOT');
        if ($root === false || $root === '') {
            self::markTestSkipped('ECCUBE_ROOT が設定されていないため結合テストをスキップします');
        }
        if (!is_file($root . '/html/require.php') || !is_file(self::cliBin($root))) {
            throw new \RuntimeException('ECCUBE_ROOT に CLI 組み込み済みの EC-CUBE がありません: ' . $root);
        }

        self::$eccubeRoot = realpath($root);
    }

    private static function cliBin(string $root): string
    {
        foreach (['/data/vendor', '/vendor'] as $vendorDir) {
            $bin = $root . $vendorDir . '/ec-cube2/cli/bin/eccube';
            if (is_file($bin)) {
                return $bin;
            }
        }

        return $root . '/data/vendor/ec-cube2/cli/bin/eccube';
    }

    /**
     * CLI を実行する。
     *
     * @param string[] $args  コマンド名と引数
     * @param string|null $stdin 標準入力に渡す文字列 (対話プロンプトへの回答)。null なら --no-interaction を付ける
     */
    protected function eccube(array $args, ?string $stdin = null): CommandResult
    {
        $command = self::php() . ' ' . escapeshellarg(self::cliBin(self::$eccubeRoot));
        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }
        if ($stdin === null) {
            $command .= ' --no-interaction';
        }

        list($exitCode, $stdout, $stderr) = self::runProcess($command, (string) $stdin);

        return new CommandResult($args, $exitCode, $stdout, $stderr);
    }

    /**
     * PHP を起動するコマンド。
     *
     * 環境変数 ECCUBE_PHP があればそれを使う (シェルのコマンドとしてそのまま展開する)。
     *
     * PHP のエラー表示 (EC-CUBE の依存ライブラリが新しい PHP で出す Deprecated など) は標準エラー出力に回し、
     * 標準出力にはコマンドの出力だけが載るようにする。Fatal error も標準エラー出力に出る。
     */
    private static function php(): string
    {
        $php = getenv('ECCUBE_PHP');
        if ($php === false || $php === '') {
            $php = escapeshellarg(PHP_BINARY);
        }

        return $php . ' -d display_errors=stderr';
    }

    /**
     * シェルコマンドを ECCUBE_ROOT で実行する。
     *
     * 出力が大きいときにパイプが詰まらないよう、標準出力と標準エラー出力は一時ファイルで受け取る。
     *
     * @return array{0: int, 1: string, 2: string} 終了コード, 標準出力, 標準エラー出力
     */
    private static function runProcess(string $command, string $stdin): array
    {
        $stdoutFile = tempnam(sys_get_temp_dir(), 'eccube-cli-test');
        $stderrFile = tempnam(sys_get_temp_dir(), 'eccube-cli-test');

        $process = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['file', $stdoutFile, 'w'], 2 => ['file', $stderrFile, 'w']],
            $pipes,
            self::$eccubeRoot
        );
        if (!is_resource($process)) {
            throw new \RuntimeException('プロセスを起動できません: ' . $command);
        }
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
        $exitCode = proc_close($process);

        $stdout = (string) file_get_contents($stdoutFile);
        $stderr = (string) file_get_contents($stderrFile);
        unlink($stdoutFile);
        unlink($stderrFile);

        return [$exitCode, $stdout, $stderr];
    }

    /**
     * 正常終了したことを確認する。失敗時はコマンドの出力全体をメッセージに含める。
     */
    protected function assertCommandSucceeded(CommandResult $result)
    {
        $this->assertSame(0, $result->exitCode, $result->describe());
    }

    protected function assertCommandFailed(CommandResult $result)
    {
        $this->assertNotSame(0, $result->exitCode, $result->describe());
    }

    /**
     * EC-CUBE の DB に SQL を発行して結果を返す (テストの事前条件や事後確認に使う)。
     *
     * CLI と同じく別プロセスで EC-CUBE を読み込んで実行する。
     *
     * @return array<int, array<string, mixed>>
     */
    protected function query(string $sql, array $params = []): array
    {
        $result = $this->runEccubePhp(sprintf(
            'echo json_encode(SC_Query_Ex::getSingletonInstance()->getAll(%s, %s));',
            var_export($sql, true),
            var_export($params, true)
        ));

        $rows = json_decode($result[1], true);
        if ($result[0] !== 0 || !is_array($rows)) {
            throw new \RuntimeException("SQL を実行できません: {$sql}\n{$result[1]}\n{$result[2]}");
        }

        return $rows;
    }

    /**
     * SQL ファイルの文を ; で区切って順に実行する (テストの事前条件を CLI に頼らずに作るために使う)。
     *
     * MySQL では、EC-CUBE の eccube_install.sh と同じく予約語の rank をバッククォートで囲む。
     *
     * @param bool $ignoreErrors true ならエラーになった文を飛ばして続ける (drop_table.sql 用)
     */
    protected function executeSqlFile(string $path, bool $ignoreErrors = false)
    {
        $result = $this->runEccubePhp(sprintf(
            '$conn = SC_Query_Ex::getSingletonInstance()->conn;'
            . '$all = file_get_contents(%s);'
            . 'if (DB_TYPE === "mysqli") { $all = str_replace("rank", "`rank`", $all); }'
            . 'foreach (explode(";", $all) as $sql) {'
            . '  if (trim($sql) === "") { continue; }'
            . '  try { $r = $conn->query($sql); } catch (Exception $e) { $r = PEAR::raiseError($e->getMessage()); }'
            . '  if (PEAR::isError($r) && !%s) { fwrite(STDERR, $r->getMessage() . ": " . $sql . "\n"); exit(1); }'
            . '}',
            var_export($path, true),
            var_export($ignoreErrors, true)
        ));

        if ($result[0] !== 0) {
            throw new \RuntimeException("SQL ファイルを実行できません: {$path}\n{$result[1]}\n{$result[2]}");
        }
    }

    /**
     * ディレクトリの中のファイルを、別のディレクトリにコピーする (サブディレクトリは扱わない)。
     */
    protected static function copyDir(string $src, string $dst)
    {
        mkdir($dst, 0777, true);
        foreach (scandir($src) as $name) {
            if ($name !== '.' && $name !== '..') {
                copy($src . '/' . $name, $dst . '/' . $name);
            }
        }
    }

    /**
     * ディレクトリを中身ごと削除する。無ければ何もしない。
     */
    protected static function removeDir(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }

    /**
     * EC-CUBE を読み込んだ別プロセスで PHP のコードを実行する。
     *
     * CLI と同じく SAFE を、テーブルが無い状態でも読み込めるよう INSTALL_FUNCTION を定義してから読み込む
     * (CLI の install 系コマンドと同じ)。
     *
     * @return array{0: int, 1: string, 2: string} 終了コード, 標準出力, 標準エラー出力
     */
    private function runEccubePhp(string $code): array
    {
        $script = 'define("SAFE", true); define("INSTALL_FUNCTION", true); '
            . 'require ' . var_export(self::$eccubeRoot . '/html/require.php', true) . '; ' . $code;

        return self::runProcess(self::php() . ' -r ' . escapeshellarg($script), '');
    }
}
