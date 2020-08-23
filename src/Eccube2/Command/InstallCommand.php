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

use Eccube2\Init;
use Eccube2\Util\Install;
use Eccube2\Util\MasterData;
use Eccube2\Util\Parameter;
use Eccube2\Util\Template;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InstallCommand extends Command
{
    protected static $defaultName = 'install';

    /** @var Install */
    protected $install;

    /** @var Parameter */
    protected $parameter;

    /** @var MasterData */
    protected $masterData;

    /** @var Template */
    protected $template;

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('インストール')
            ->addArgument('shop_name', InputArgument::OPTIONAL, '店名')
            ->addArgument('admin_mail', InputArgument::OPTIONAL, '管理者メールアドレス')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'YES')
            ->addOption('send_info', null, InputOption::VALUE_NONE, 'インストール情報を送信')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        define('SAFE', true);
        define('INSTALL_FUNCTION', true);
        Init::init();

        $this->install = new Install();
        $this->parameter = new Parameter();
        $this->masterData = new MasterData();
        $this->template = new Template();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('EC-CUBE2 インストール');

        $io->section('ソフトウェア使用許諾書');
        $io->writeln($this->install->agreement());
        if (!$io->confirm('ソフトウェア使用許諾書に同意しますか？', false)) {
            return;
        }

        $io->section('テーブル作成');
        if ($input->getOption('yes') || $io->confirm('テーブルを作成しますか？')) {
            $io->text('テーブルの作成を開始します。');
            $this->install->createTable();
            $io->success('テーブルの作成に成功しました。');
        } else {
            $io->success('テーブルの作成をスキップしました。');
        }

        $io->section('初期データ作成');
        if ($input->getOption('yes') || $io->confirm('初期データを作成しますか？')) {
            $io->text('初期データの作成を開始します。');
            $this->install->insertData();
            $io->success('初期データの作成に成功しました。');
        } else {
            $io->success('初期データの作成をスキップしました。');
        }

        $io->section('シーケンス作成');
        if ($input->getOption('yes') || $io->confirm('シーケンスを作成しますか？')) {
            $this->install->createSequence();
            $io->success('シーケンスの作成に成功しました。');
        } else {
            $io->success('シーケンスの作成をスキップしました。');
        }

        $io->section('店舗情報設定');
        $shopName = $input->getArgument('shop_name');
        if (!$shopName) {
            $io->ask('店名を入力してください', null, function ($shopName) {
                if (empty($shopName)) {
                    throw new \RuntimeException('店名は空にできません。');
                }

                return $shopName;
            });
        }
        $adminMail = $input->getArgument('admin_mail');
        if (!$adminMail) {
            $io->ask('管理者メールアドレスを入力してください', null, function ($adminMail) {
                if (empty($adminMail)) {
                    throw new \RuntimeException('管理者メールアドレスは空にできません。');
                }

                return $adminMail;
            });
        }
        $this->install->setBaseInfo($shopName, $adminMail);
        $io->success('店舗情報設定に成功しました。');

        $io->section('画像コピー');
        $message = $this->install->copyImage();
        $io->block($message);

        $io->section('キャッシュクリア');
        $this->masterData->clearAllCache();
        $this->parameter->createCache();
        $this->template->clearAllCache();
        $io->success('キャッシュをクリアしました。');

        $io->section('インストール情報送信');
        $arrSendData = $this->install->getSendInfo();
        $io->title('サイト情報');
        $io->section('サイトURL');
        $io->text($arrSendData['site_url']);
        $io->section('店名');
        $io->text($arrSendData['shop_name']);
        $io->section('EC-CUBEバージョン');
        $io->text($arrSendData['cube_ver']);
        $io->section('PHP情報');
        $io->text($arrSendData['php_ver']);
        $io->section('DB情報');
        $io->text($arrSendData['db_ver']);
        $io->section('OS情報');
        $io->text($arrSendData['os_type']);
        if ($input->getOption('send_info') || $io->confirm('株式会社EC-CUBEにインストール情報を送信しますか？')) {
            $this->install->sendInfoExecute($arrSendData);
            $io->success('インストール情報を送信しました。');
        }

        $io->success(array(
            'インストールが完了しました。',
            HTTPS_URL . ' からアクセスしてください。',
        ));
    }
}
