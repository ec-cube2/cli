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
 *
 * 環境変数 ECCUBE_RESTRICTED_PERMISSIONS=1 のときは、Web サーバーの書き込み権限を絞った環境
 * (.github/docker/restricted/、ec-cube2/cli#20) で実行しているものとして、CLI を実行するたびに権限の問題が
 * 起きていないかを確かめる。問題があったテストと、失敗したテストは、未完了 (incomplete) として記録する。
 * この環境で動かないコマンドを一覧にするためで、CLI の不具合そのものは通常の結合テストで確かめる。
 * CLI (Init::setErrorHandler()) は E_WARNING を表示しないので、ファイルを書けなくても出力には何も出ないことが多い。
 * 権限の問題は、主に各テストの結果の確認 (ファイルが更新されたか) と、所有者・トップページの確認で見つける。
 */
abstract class IntegrationTestCase extends TestCase
{
    /** @var string */
    protected static $eccubeRoot;

    /**
     * このテストで CLI を実行したときに見つかった権限の問題。
     *
     * @var string[]
     */
    private $permissionProblems = [];

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
        $result = new CommandResult($args, $exitCode, $stdout, $stderr);

        if (self::isRestrictedPermissions()) {
            foreach ($this->findPermissionProblems($result) as $problem) {
                $this->permissionProblems[] = 'eccube ' . implode(' ', $args) . ': ' . $problem;
            }
        }

        return $result;
    }

    /**
     * Web サーバーの書き込み権限を絞った環境で実行しているか (ECCUBE_RESTRICTED_PERMISSIONS=1)。
     */
    protected static function isRestrictedPermissions(): bool
    {
        return getenv('ECCUBE_RESTRICTED_PERMISSIONS') === '1';
    }

    /**
     * Web サーバーが書き込めるディレクトリ (ECCUBE_ROOT からの相対パス。環境変数 ECCUBE_WRITABLE_DIRS)。
     *
     * @return string[]
     */
    protected static function writableDirs(): array
    {
        return preg_split('/\s+/', trim((string) getenv('ECCUBE_WRITABLE_DIRS')), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * テストは通ったが、CLI の実行で権限の問題が起きていた場合は未完了にする。
     */
    protected function assertPostConditions(): void
    {
        if ($this->permissionProblems !== []) {
            $this->markTestIncomplete($this->describePermissionProblems());
        }
    }

    /**
     * 権限を絞った環境でのテストの失敗は、未完了に置き換える (この環境で動かないものを一覧にするため)。
     *
     * 通常の結合テストで通ることを前提に、この環境で失敗したものは権限が原因とみなす。
     */
    protected function onNotSuccessfulTest(\Throwable $t): void
    {
        if (self::isRestrictedPermissions()
            && !$t instanceof \PHPUnit\Framework\IncompleteTest
            && !$t instanceof \PHPUnit\Framework\SkippedTest) {
            $message = 'Web サーバーの書き込み権限を絞った環境で失敗しました';
            if ($this->permissionProblems !== []) {
                $message = $this->describePermissionProblems();
            }
            $this->markTestIncomplete($message . "\n\n--- テストの失敗 ---\n" . $t->getMessage());
        }

        parent::onNotSuccessfulTest($t);
    }

    private function describePermissionProblems(): string
    {
        return "Web サーバーの書き込み権限を絞った環境で問題が起きました:\n- "
            . implode("\n- ", array_unique($this->permissionProblems));
    }

    /**
     * 所有者が想定と違うファイルのうち、報告済みのもの (後続のテストで同じものを何度も報告しないため)。
     *
     * @var array<string, true>
     */
    private static $reportedOwnershipProblems = [];

    /** @var bool 直前の確認で、トップページを表示できなかったか */
    private static $webWasBroken = false;

    /**
     * CLI を実行した後に、権限の問題が起きていないかを確かめる。
     *
     * - 出力に、権限が無いことによるエラーが出ていない (EC-CUBE のエラーハンドラーは警告を標準出力に出す)。
     *   ただし data/logs/ に書けないことによる警告は、どのコマンドでも出る既知の問題なので
     *   RestrictedPermissionsTest で別に記録し、ここでは数えない。
     * - Web サーバーが書き込めるディレクトリの中は Web サーバーの所有、ほかは SSH ユーザーの所有のまま。
     *   ほかの場所に、誰でも書き込めるファイルができていない。
     * - Web サーバーがトップページを表示できる (CLI が書き換えたファイルを Web 側で読める)。
     *
     * @return string[]
     */
    private function findPermissionProblems(CommandResult $result): array
    {
        $problems = [];

        foreach (preg_split('/\R/', $result->output()) as $line) {
            if (preg_match('/Permission denied|[Ff]ailed to open stream|Operation not permitted|Read-only file system/', $line)
                && strpos($line, '/data/logs/') === false) {
                $problems[] = '出力: ' . trim(strip_tags($line));
            }
        }

        $newOwnershipProblems = [];
        foreach (self::findOwnershipProblems() as $problem) {
            if (!isset(self::$reportedOwnershipProblems[$problem])) {
                self::$reportedOwnershipProblems[$problem] = true;
                $newOwnershipProblems[] = $problem;
            }
        }
        // バックアップのように 1 回で多くのファイルを作るコマンドがあるので、最初の 5 件だけを出す
        foreach (array_slice($newOwnershipProblems, 0, 5) as $problem) {
            $problems[] = '所有者: ' . $problem;
        }
        if (count($newOwnershipProblems) > 5) {
            $problems[] = sprintf('所有者: ほか %d 件', count($newOwnershipProblems) - 5);
        }

        if (!$this->checksWebPage()) {
            return $problems;
        }
        $webProblem = self::findWebProblem();
        if ($webProblem !== null && !self::$webWasBroken) {
            $problems[] = 'Web: ' . $webProblem;
        }
        self::$webWasBroken = $webProblem !== null;

        return $problems;
    }

    /**
     * CLI を実行した後に、Web サーバーでトップページを表示できるかを確かめるか。
     *
     * DB を作り直すテストのように、途中でトップページを表示できなくなるのが正しいものでは false にする。
     */
    protected function checksWebPage(): bool
    {
        return true;
    }

    /**
     * @return string[] 所有者か mode が想定と違うファイル
     */
    protected static function findOwnershipProblems(): array
    {
        $root = self::$eccubeRoot;
        $siteUid = (int) getenv('ECCUBE_SITE_UID');
        $writable = [];
        foreach (self::writableDirs() as $dir) {
            // 書き込み用ディレクトリの中は、そのディレクトリと同じ所有者 (restrict-permissions.sh が Web サーバーにしている)
            $writable[$dir] = fileowner($root . '/' . $dir);
        }

        $problems = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                function (\SplFileInfo $file) use ($root) {
                    // Composer の vendor は CLI が書き換えない。数が多いので見ない
                    $relative = substr($file->getPathname(), strlen($root) + 1);

                    return $relative !== 'data/vendor' && $relative !== 'vendor';
                }
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isLink()) {
                continue;
            }
            $relative = substr($file->getPathname(), strlen($root) + 1);
            $expected = $siteUid;
            $inWritable = false;
            foreach ($writable as $dir => $uid) {
                if ($relative === $dir || strpos($relative, $dir . '/') === 0) {
                    $expected = $uid;
                    $inWritable = true;
                    break;
                }
            }
            if ($file->getOwner() !== $expected) {
                $problems[] = sprintf('%s (uid %d。想定は uid %d)', $relative, $file->getOwner(), $expected);
            } elseif (!$inWritable && ($file->getPerms() & 0002) !== 0) {
                $problems[] = sprintf('%s (誰でも書き込める: %o)', $relative, $file->getPerms() & 0777);
            }
        }

        return $problems;
    }

    /**
     * Web サーバーでトップページを表示できるかを確かめる (環境変数 ECCUBE_WEB_URL)。
     *
     * @return string|null 表示できないときはその理由
     */
    protected static function findWebProblem()
    {
        $url = (string) getenv('ECCUBE_WEB_URL');
        $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 30]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return $url . ' に接続できません';
        }
        // リダイレクトされたときは、それぞれの応答のヘッダーが続けて入るので、最後のステータス行を見る
        $status = '';
        foreach ($http_response_header as $header) {
            if (strpos($header, 'HTTP/') === 0) {
                $status = $header;
            }
        }
        if (!preg_match('#^HTTP/\S+ 200#', $status)) {
            return $url . ': ' . $status;
        }
        if (stripos($body, '</html>') === false) {
            return $url . ': ページの最後まで出力されていません' . "\n" . substr($body, -500);
        }

        return null;
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
    protected function runEccubePhp(string $code): array
    {
        $script = 'define("SAFE", true); define("INSTALL_FUNCTION", true); '
            . 'require ' . var_export(self::$eccubeRoot . '/html/require.php', true) . '; ' . $code;

        return self::runProcess(self::php() . ' -r ' . escapeshellarg($script), '');
    }
}
