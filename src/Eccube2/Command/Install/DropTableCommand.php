<?php

namespace Eccube2\Command\Install;

use Eccube2\Init;
use Eccube2\Util\InstallUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DropTableCommand extends Command
{
    protected static $defaultName = 'install:drop-table';

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
            ->setDescription('インストール テーブル削除')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'YES')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('yes') && !$io->confirm('本当に削除しますか？', false)) {
            return;
        }

        $io->text('テーブルの削除を開始します。');

        $this->install->createTable();

        $io->success('テーブルの削除に成功しました。');
    }
}
