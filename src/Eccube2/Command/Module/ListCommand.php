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
use Eccube2\Util\ModuleUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListCommand extends Command
{
    protected static $defaultName = 'module:list';

    /** @var ModuleUtil */
    protected $module;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->module = new ModuleUtil();
    }

    protected function configure()
    {
        $this
            ->setDescription('モジュール一覧')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->module->getAll();

        $rows = array();
        foreach ($modules as $module) {
            $rows[] = array(
                $module['module_code'],
                mb_strimwidth($module['module_name'], 0, 35, '...'),
            );
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('モジュール一覧');
        $io->table(array('Code', 'Name'), $rows);

        $io->newLine();
    }
}
