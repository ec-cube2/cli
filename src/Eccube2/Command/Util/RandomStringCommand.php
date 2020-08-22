<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command\Util;

use Eccube2\Init;
use Eccube2\Util\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RandomStringCommand extends Command
{
    protected static $defaultName = 'util:random-string';

    /** @var Util */
    protected $util;

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('ランダムな文字列を生成')
            ->addArgument('length', InputArgument::OPTIONAL, '文字数', 40)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->util = new Util();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $length = $input->getArgument('length');
        $io->writeln($this->util->getRandomString($length));
    }
}
