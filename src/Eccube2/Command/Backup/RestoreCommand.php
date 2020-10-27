<?php

namespace Eccube2\Command\Backup;

use Eccube2\Init;
use Eccube2\Util\BackupUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RestoreCommand extends Command
{
    protected static $defaultName = 'backup:restore';

    /** @var BackupUtil */
    protected $backup;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->backup = new BackupUtil();
    }

    protected function configure()
    {
        $this
            ->setDescription('バックアップリストア')
            ->addArgument('name', InputArgument::REQUIRED, 'バックアップ名')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'YES')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'エラーを無視')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('yes') && !$io->confirm('本当にリストアしますか？', false)) {
            return;
        }

        $name = $input->getArgument('name');
        $force = $input->getOption('force') ? true : false;

        $io->text($name . ' のリストアを開始します。');
        $this->backup->restore($name, $force);
        $io->success($name . ' のリストアを完了しました。');
    }
}
