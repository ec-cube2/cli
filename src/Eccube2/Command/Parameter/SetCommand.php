<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command\Parameter;

use Eccube2\Init;
use Eccube2\Util\Parameter;
use Eccube2\Util\Template;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetCommand extends Command
{
    protected static $defaultName = 'parameter:set';

    /** @var Parameter */
    protected $parameter;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->parameter = new Parameter();
    }

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('パラメーター設定')
            ->addArgument('key', InputArgument::REQUIRED, '定数名')
            ->addArgument('value', InputArgument::REQUIRED, 'パラメーター値')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        $this->parameter->set($key, $value);

        $output->writeln('    <info>設定しました。</info>');
    }
}
