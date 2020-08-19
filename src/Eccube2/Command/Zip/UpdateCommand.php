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
            ->setName(static::$defaultName)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $progressStart = false;
            $count = $this->zip->update(function ($cntCurrentLine, $cntLine) use ($io, $progressStart) {
                if ($progressStart === false) {
                    $io->progressStart($cntCurrentLine);
                    $progressStart = true;
                }

                $io->progressAdvance($cntCurrentLine);
            });

            $io->progressFinish();
            $io->text($count . ' 件を追加しました。');
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return;
        }

        $io->success('郵便番号の更新が完了しました。');
    }
}
