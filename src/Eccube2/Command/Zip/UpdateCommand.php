<?php

namespace Eccube2\Command\Zip;

use Eccube2\Init;
use Eccube2\Util\Zip;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateCommand extends Command
{
    protected static $defaultName = 'zip:update';

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
            ->setDescription('郵便番号更新')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->begin();

        $io->text('ZIPダウンロードを開始します。');
        $this->zip->download();
        $io->success('ZIPダウンロードが完了しました。');

        $io->text('既存データの削除を開始します。');
        $this->zip->delete();
        $io->success('既存データの削除が完了しました。');

        $progressStart = false;
        $io->text('アップデートを開始します。');
        $count = $this->zip->insert(1, function ($cntCurrentLine, $cntLine) use ($io, &$progressStart) {
            if ($progressStart === false) {
                $io->newLine();

                $io->progressStart($cntLine);
                $progressStart = true;
            }

            if ($cntCurrentLine % 100 === 0) {
                $io->progressAdvance(100);
            }
        });

        if ($progressStart) {
            $io->progressFinish();
        }
        $io->success($count . ' 件を追加しました。');

        $objQuery->commit();

        $io->success('郵便番号の更新が完了しました。');
    }
}
