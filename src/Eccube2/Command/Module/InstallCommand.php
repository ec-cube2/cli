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

class InstallCommand extends Command
{
    protected static $defaultName = 'module:install';

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
            ->setName(static::$defaultName)
            ->setDescription('モジュールインストール')
            ->addArgument('code', InputArgument::REQUIRED, 'モジュールコード')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('code');

        if (!$this->module->isInstalled($code)) {
            $id = $this->module->install($code);
        } else {
            $id = $this->module->update($code);
        }

        $output->writeln('    <info>モジュールをインストールしました.</info>');
        $output->writeln('    <info>'.$this->module->getConfigUrlById($id).' から設定してください</info>');
    }
}
