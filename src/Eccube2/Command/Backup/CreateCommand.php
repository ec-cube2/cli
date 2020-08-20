<?php

namespace Eccube2\Command\Backup;

use Eccube2\Init;
use Eccube2\Util\Backup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateCommand extends Command
{
    protected static $defaultName = 'backup:create';

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
            ->setName(static::$defaultName)
            ->setDescription('バックアップ作成')
            ->addArgument('name', InputArgument::REQUIRED, 'バックアップ名')
            ->addArgument('memo', InputArgument::OPTIONAL, 'バックアップメモ')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $name = $input->getArgument('name');
        $memo = $input->getArgument('memo');

        $io->text('バックアップを開始します。');
        $this->backup->create($name, $memo);

        $io->success('バックアップ完了しました。');
    }
}
