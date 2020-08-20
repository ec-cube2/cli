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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetCommand extends Command
{
    protected static $defaultName = 'parameter:get';

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
            ->setDescription('パラメーター表示')
            ->addArgument('key', InputArgument::REQUIRED, '定数名')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');

        $value = $this->parameter->get($key);

        $output->writeln('    <info>' . $value . '</info>');
    }
}
