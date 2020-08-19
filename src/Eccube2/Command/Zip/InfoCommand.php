<?php

namespace Eccube2\Command\Zip;

use Eccube2\Init;
use Eccube2\Util\Zip;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InfoCommand extends Command
{
    protected static $defaultName = 'zip:info';

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

        $io->title('郵便番号情報');

        $io->section('郵便番号CSV');
        $io->text(number_format($this->zip->countCsv()) . ' 行 (更新日時: ' . $this->zip->getCsvDateTime() . ' )');

        $io->section('郵便番号DB');
        $io->text(number_format($this->zip->count()) . ' 行');
    }
}
