<?php

namespace Eccube2\Command\Install;

use Eccube2\Init;
use Eccube2\Util\InstallUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConfigCommand extends Command
{
    protected static $defaultName = 'install:config';

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
            ->setDescription('インストール 設定')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (function_exists('env')) {
            $io->comment('設定は「サーバの環境変数」もしくは「.env」から行ってください。');
            return;
        }

        $this->install->config();

        $io->success('設定が完了しました。');
    }
}
