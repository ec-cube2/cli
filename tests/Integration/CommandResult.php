<?php

namespace Eccube2\Tests\Integration;

/**
 * CLI を別プロセスで実行した結果。
 */
class CommandResult
{
    /** @var string[] */
    public $args;

    /** @var int */
    public $exitCode;

    /** @var string */
    public $stdout;

    /** @var string */
    public $stderr;

    public function __construct(array $args, int $exitCode, string $stdout, string $stderr)
    {
        $this->args = $args;
        $this->exitCode = $exitCode;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    /**
     * 標準出力と標準エラー出力を結合したもの。
     */
    public function output(): string
    {
        return $this->stdout . $this->stderr;
    }

    /**
     * アサーション失敗時のメッセージ用。
     */
    public function describe(): string
    {
        return sprintf(
            "eccube %s (exit code: %d)\n--- stdout ---\n%s\n--- stderr ---\n%s",
            implode(' ', $this->args),
            $this->exitCode,
            $this->stdout,
            $this->stderr
        );
    }
}
