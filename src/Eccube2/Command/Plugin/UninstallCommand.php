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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UninstallCommand extends Command
{
    protected static $defaultName = 'plugin:uninstall';

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
            ->setDescription('プラグインアンインストール')
            ->addArgument('code', InputArgument::REQUIRED, 'プラグインコード')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $code = $input->getArgument('code');

        if (!$this->plugin->isInstalled($code)) {
            throw new \InvalidArgumentException($code.' はインストールされていません。');
        }

        $this->plugin->uninstall($code);

        $io->success($code . ' をアンインストールしました');
    }
}
