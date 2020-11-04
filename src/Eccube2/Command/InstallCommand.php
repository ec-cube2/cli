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
use Eccube2\Util\InstallUtil;
use Eccube2\Util\MasterDataUtil;
use Eccube2\Util\ParameterUtil;
use Eccube2\Util\TemplateUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InstallCommand extends Command
{
    protected static $defaultName = 'install';

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var InstallUtil */
    protected $install;

    /** @var ParameterUtil */
    protected $parameter;

    /** @var MasterDataUtil */
    protected $masterData;

    /** @var TemplateUtil */
    protected $template;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct(null);

        $this->eventDispatcher = $eventDispatcher;
    }

    protected function configure()
    {
        $this
            ->setDescription('インストール')
            ->addArgument('shop_name', InputArgument::OPTIONAL, '店名')
            ->addArgument('admin_mail', InputArgument::OPTIONAL, '管理者メールアドレス')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'YES')
            ->addOption('no-send-info', null, InputOption::VALUE_NONE, 'インストール情報を送信しない')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        define('INSTALL_FUNCTION', true);
        Init::init();

        $this->install = new InstallUtil();
        $this->parameter = new ParameterUtil();
        $this->masterData = new MasterDataUtil();
        $this->template = new TemplateUtil();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('EC-CUBE2 インストール');

        $io->section('ソフトウェア使用許諾書');
        $io->writeln($this->install->agreement());
        if (!$input->getOption('yes') && !$io->confirm('ソフトウェア使用許諾書に同意しますか？', false)) {
            $io->error('ソフトウェア使用許諾書に同意してください。');

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
            $this->eventDispatcher->dispatch('install.insert_data');
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
        if (empty($shopName)) {
            $shopName = $io->ask('店名を入力してください', null, function ($shopName) {
                if (empty($shopName)) {
                    throw new \RuntimeException('店名は空にできません。');
                }

                return $shopName;
            });
        }
        $adminMail = $input->getArgument('admin_mail');
        if (empty($adminMail)) {
            $adminMail = $io->ask('管理者メールアドレスを入力してください', null, function ($adminMail) {
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
        $io->success('画像をコピーしました。');

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
        if (!$input->getOption('no-send-info') && $io->confirm('株式会社EC-CUBEにインストール情報を送信しますか？')) {
            $this->install->sendInfoExecute($arrSendData);
            $io->success('インストール情報を送信しました。');
        }

        $this->eventDispatcher->dispatch('install.after');

        $io->success(array(
            'インストールが完了しました。',
            HTTPS_URL . ' からアクセスしてください。',
        ));
    }
}
