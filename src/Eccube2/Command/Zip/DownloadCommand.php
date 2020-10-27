<?php

namespace Eccube2\Command\Zip;

use Eccube2\Init;
use Eccube2\Util\ZipUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DownloadCommand extends Command
{
    protected static $defaultName = 'zip:download';

    /** @var ZipUtil */
    protected $zip;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->zip = new ZipUtil();
    }

    protected function configure()
    {
        $this
            ->setDescription('郵便番号CSVダウンロード')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->text('郵便番号CSVを ' .  ZIP_DOWNLOAD_URL . ' から取得します。');

        $this->zip->download();

        $io->success('郵便番号CSVを取得しました。');
    }
}
