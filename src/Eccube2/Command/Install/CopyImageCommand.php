<?php

namespace Eccube2\Command\Install;

use Eccube2\Init;
use Eccube2\Util\Install;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CopyImageCommand extends Command
{
    protected static $defaultName = 'install:copy-image';

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
            ->setName(static::$defaultName)
            ->setDescription('インストール 画像コピー')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $message = $this->install->copyImage();

        $io->block($message);
    }
}
