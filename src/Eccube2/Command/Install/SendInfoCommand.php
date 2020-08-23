<?php

namespace Eccube2\Command\Install;

use Eccube2\Init;
use Eccube2\Util\Install;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SendInfoCommand extends Command
{
    protected static $defaultName = 'install:send-info';

    /** @var Install */
    protected $install;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        define('INSTALL_FUNCTION', true);
        Init::init();

        $this->install = new Install();
    }

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('インストール インストール情報送信')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'YES')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

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
        if (!$input->getOption('yes') && !$io->confirm('株式会社EC-CUBEにインストール情報を送信しますか？')) {
            return;
        }
        $this->install->sendInfoExecute($arrSendData);
        $io->success('インストール情報を送信しました。');
    }
}
