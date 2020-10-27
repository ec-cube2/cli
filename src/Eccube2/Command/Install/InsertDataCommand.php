<?php

namespace Eccube2\Command\Install;

use Eccube2\Init;
use Eccube2\Util\InstallUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InsertDataCommand extends Command
{
    protected static $defaultName = 'install:insert-data';

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var InstallUtil */
    protected $install;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        parent::__construct(null);

        $this->eventDispatcher = $eventDispatcher;
    }

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        define('INSTALL_FUNCTION', true);
        Init::init();

        $this->install = new InstallUtil();
    }

    protected function configure()
    {
        $this
            ->setDescription('インストール 初期データ作成')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'YES')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('yes') && !$io->confirm('初期データを作成しますか？')) {
            return;
        }
        $io->text('初期データの作成を開始します。');
        $this->install->insertData();
        $this->eventDispatcher->dispatch('install.insert_data');
        $io->success('初期データの作成に成功しました。');
    }
}
