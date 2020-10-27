<?php

namespace Eccube2\Command\Install;

use Eccube2\Init;
use Eccube2\Util\InstallUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetBaseInfoCommand extends Command
{
    protected static $defaultName = 'install:set-base-info';

    /** @var InstallUtil */
    protected $install;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        define('INSTALL_FUNCTION', true);
        Init::init();

        $this->install = new InstallUtil();
    }

    protected function configure()
    {
        $this
            ->setDescription('インストール 店舗情報設定')
            ->addArgument('shop_name', InputArgument::OPTIONAL, '店名')
            ->addArgument('admin_mail', InputArgument::OPTIONAL, '管理者メールアドレス')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

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
    }
}
