<?php

namespace Eccube2\Command\Install;

use Eccube2\Init;
use Eccube2\Util\Install;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateSequenceCommand extends Command
{
    protected static $defaultName = 'install:create-sequence';

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
            ->setDescription('インストール シーケンス作成')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'YES')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('yes') && !$io->confirm('シーケンスを作成しますか？')) {
            return;
        }
        $this->install->createSequence();
        $io->success('シーケンスの作成に成功しました。');
    }
}
