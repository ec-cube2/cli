<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command\Module;

use Eccube2\Init;
use Eccube2\Util\Module;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    protected static $defaultName = 'module:update';

    /** @var Module */
    protected $module;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->module = new Module();
    }

    protected function configure()
    {
        $this
            ->setDescription('モジュールアップデート')
            ->addArgument('code', InputArgument::REQUIRED, 'モジュールコード')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('code');
        $this->module->update($code);

        $output->writeln('    <info>モジュールをアップデートしました.</info>');
        $output->writeln('    <info>'.$this->module->getConfigUrlByCode($code).' から設定してください</info>');
    }
}
