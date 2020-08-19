<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command\Plugin;

use Eccube2\Init;
use Eccube2\Util\Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListCommand extends Command
{
    protected static $defaultName = 'plugin:list';

    /** @var Plugin */
    protected $plugin;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->plugin = new Plugin();
    }

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plugins = $this->plugin->getAll();

        $rows = array();
        foreach ($plugins as $plugin) {
            $rows[] = array(
                $plugin['plugin_code'],
                mb_strimwidth($plugin['plugin_name'], 0, 35, '...'),
                $plugin['enable'] ? 'o' : 'x',
            );
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('プラグイン一覧');
        $io->table(array('Code', 'Name', 'Enable'), $rows);

        $io->newLine();
    }
}
