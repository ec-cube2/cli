<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginUninstallCommand extends BasePluginCommand
{
    protected static $defaultName = 'plugin:uninstall';

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->addArgument('plugin_name', InputArgument::REQUIRED, 'プラグイン名')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->uninstall($input, $output);
    }
}
