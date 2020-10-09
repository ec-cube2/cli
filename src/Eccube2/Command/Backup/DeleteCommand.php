<?php

namespace Eccube2\Command\Backup;

use Eccube2\Init;
use Eccube2\Util\Backup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteCommand extends Command
{
    protected static $defaultName = 'backup:delete';

    /** @var Backup */
    protected $backup;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->backup = new Backup();
    }

    protected function configure()
    {
        $this
            ->setDescription('バックアップ削除')
            ->addArgument('name', InputArgument::REQUIRED, 'バックアップ名')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'YES')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->getOption('yes') && !$io->confirm('本当に削除しますか？', false)) {
            return;
        }

        $name = $input->getArgument('name');

        $io->text('バックアップを削除します。');
        $this->backup->delete($name);

        $io->success('バックアップを削除しました。');
    }
}
