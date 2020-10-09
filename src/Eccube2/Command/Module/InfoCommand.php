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
use Symfony\Component\Console\Style\SymfonyStyle;

class InfoCommand extends Command
{
    protected static $defaultName = 'module:info';

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
            ->setDescription('モジュール情報')
            ->addArgument('code', InputArgument::REQUIRED, 'モジュールコード')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('code');

        $arrModule = $this->module->findOneByCode($code);
        if (!$arrModule) {
            throw new \InvalidArgumentException($code.' はインストールされていません。');
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('モジュール情報');

        $io->section('Id');
        $io->text($arrModule['module_id']);

        $io->section('Code');
        $io->text($arrModule['module_code']);

        $io->section('Name');
        $io->text($arrModule['module_name']);

        $io->section('Config Url');
        $io->text($this->module->getConfigUrl($arrModule));

        $io->section('Create Date');
        $io->text($arrModule['create_date']);

        $io->section('Update Date');
        $io->text($arrModule['update_date']);

        $io->newLine();
    }
}
