<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command\Backup;

use Eccube2\Init;
use Eccube2\Util\BackupUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListCommand extends Command
{
    protected static $defaultName = 'backup:list';

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
            ->setDescription('バックアップ一覧')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $backups = $this->backup->getAll();

        $rows = array();
        foreach ($backups as $backup) {
            $rows[] = array(
                $backup['bkup_name'],
                mb_strimwidth($backup['bkup_memo'], 0, 35, '...'),
                $backup['create_date'],
            );
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('バックアップ一覧');
        $io->table(array('名称', 'メモ', '作成日'), $rows);

        $io->newLine();
    }
}
