<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InfoCommand extends BasePluginCommand
{
    protected static $defaultName = 'info';

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $baseDir = __DIR__.'/../../../../../../';
        require_once $baseDir . '/html/require.php';

        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストール後にプラグインを追加してください.');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $io = new SymfonyStyle($input, $output);
        $io->title('EC-CUBE情報');

        $io->section('EC-CUBEバージョン');
        $io->text(ECCUBE_VERSION);


        $io->title('OS情報');

        $io->section('オペレーティングシステム');
        $io->text(php_uname('s'));

        $io->section('ホスト名');
        $io->text(php_uname('n'));

        $io->section('リリース名');
        $io->text(php_uname('r'));

        $io->section('バージョン情報');
        $io->text(php_uname('v'));

        $io->section('マシン型');
        $io->text(php_uname('m'));

        $io->title('PHP情報');

        $io->section('PHPバージョン');
        $io->text($this->getPHPVersion());

        $io->section('PHPモジュール');
        $io->listing($this->getPHPModule());

        $io->title('DB情報');

        $io->section('DBバージョン');
        $io->text($this->getDBVersion());
    }

    private function getDBVersion()
    {
        $dbFactory = \SC_DB_DBFactory_Ex::getInstance();

        return $dbFactory->sfGetDBVersion();
    }

    private function getPHPVersion()
    {
        return 'PHP ' . phpversion();
    }

    private function getPHPModule()
    {
        return get_loaded_extensions();
    }
}
