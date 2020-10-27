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

class InfoCommand extends Command
{
    protected static $defaultName = 'plugin:info';

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
            ->setDescription('プラグイン情報')
            ->addArgument('code', InputArgument::REQUIRED, 'プラグインコード')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('code');

        $plugin = $this->plugin->isInstalled($code);
        if (!$plugin) {
            throw new \InvalidArgumentException($code.' はインストールされていません。');
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('プラグイン情報');

        $io->section('Id');
        $io->text($plugin['plugin_id']);

        $io->section('Code');
        $io->text($plugin['plugin_code']);

        $io->section('Name');
        $io->text($plugin['plugin_name']);

        if ($plugin['plugin_description']) {
            $io->section('Description');
            $io->text($plugin['plugin_description']);
        }

        $io->section('Class Name');
        $io->text($plugin['class_name']);

        if ($plugin['plugin_version']) {
            $io->section('Version');
            $io->text($plugin['plugin_version']);
        }

        if ($plugin['author']) {
            $io->section('Author');
            $io->text($plugin['author']);
        }

        if ($plugin['author_site_url']) {
            $io->section('Author Site Url');
            $io->text($plugin['author_site_url']);
        }

        if ($plugin['plugin_site_url']) {
            $io->section('Plugin Site Url');
            $io->text($plugin['plugin_site_url']);
        }

        if ($plugin['compliant_version']) {
            $io->section('Compliant Version');
            $io->text($plugin['compliant_version']);
        }

        $io->section('Priority');
        $io->text($plugin['priority']);

        $io->section('Enable');
        $io->text($plugin['enable'] ? 'o' : 'x');

        $io->section('Create Date');
        $io->text($plugin['create_date']);

        $io->section('Update Date');
        $io->text($plugin['update_date']);


        $io->newLine();
    }
}
