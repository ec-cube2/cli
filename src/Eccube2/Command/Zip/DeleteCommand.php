<?php

namespace Eccube2\Command\Zip;

use Eccube2\Init;
use Eccube2\Util\Zip;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteCommand extends Command
{
    protected static $defaultName = 'zip:delete';

    /** @var Zip */
    protected $zip;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->zip = new Zip();
    }

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('郵便番号データ削除')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'YES')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('yes') && !$io->confirm('本当に削除しますか？', false)) {
            return;
        }

        $this->zip->delete();

        $io->success('郵便番号データを削除しました。');
    }
}
