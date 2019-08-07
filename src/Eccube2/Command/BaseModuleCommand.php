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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseModuleCommand extends Command
{
    protected $moduleDir;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $baseDir = __DIR__.'/../../../../../../';
        require_once $baseDir . '/html/require.php';

        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストール後にプラグインを追加してください.');
        }

        $this->moduleDir = rtrim(MODULE_REALDIR, '/');
    }

    public function isInstalled($code)
    {
        $eccubeModule = $this->getEccubeModule($code);

        return !empty($eccubeModule) ? true : false;
    }

    public function listModule(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->getEccubeModules();

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
    }

    public function info(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('module_name');

        $module = $this->getEccubeModule($code);
        if (!$module) {
            throw new \InvalidArgumentException($code.' はインストールされていません。');
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('モジュール情報');

        $io->section('Id');
        $io->text($module['module_id']);

        $io->section('Code');
        $io->text($module['module_code']);

        $io->section('Name');
        $io->text($module['module_name']);

        $io->section('Create Date');
        $io->text($module['create_date']);

        $io->section('Update Date');
        $io->text($module['update_date']);
    }

    /**
     * {@inheritDoc}
     */
    public function install(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('module_name');

        if (!$this->isInstalled($code)) {
            $module_id = $this->installEccubeModule($code);
        } else {
            $module_id = $this->updateEccubeModule($code);
        }
        $output->writeln('    <info>モジュールをインストールしました.</info>');
        $output->writeln('    <info>'.HTTPS_URL.ADMIN_DIR.'load_module_config.php?module_id='.$module_id.' から設定してください</info>');
    }

    /**
     * {@inheritDoc}
     */
    public function update(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('module_name');

        $module_id = $this->updateEccubeModule($code);
        $output->writeln('    <info>モジュールをアップデートしました.</info>');
        $output->writeln('    <info>'.HTTPS_URL.ADMIN_DIR.'load_module_config.php?module_id='.$module_id.' から設定してください</info>');
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('module_name');

        $this->uninstallEccubeModule($code);
        $output->writeln('<info>'.$code.'</info>');
        $output->writeln('    <info>Uninstall Success</info>');
    }

    protected function installEccubeModule($code)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $arrModule = array(
            'module_id'   => max($objQuery->max('module_id', 'dtb_module'), 1000000) + 1,
            'module_code' => $code,
            'module_name' => '',
            'auto_update_flg' => '0',
            'create_date'     => 'CURRENT_TIMESTAMP',
            'update_date' => 'CURRENT_TIMESTAMP'
        );
        $objQuery->insert('dtb_module', $arrModule, 'module_code = ?', array($code));

        return $arrModule['module_id'];
    }

    protected function updateEccubeModule($code)
    {
        $arrModule = $this->getEccubeModule($code);
        $arrUpdate = array(
            'module_code' => $code,
            'module_name' => '',
            'update_date' => 'CURRENT_TIMESTAMP'
        );
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->update('dtb_module', $arrUpdate, 'module_id = ?', array($arrModule['module_id']));

        return $arrModule['module_id'];
    }

    protected function uninstallEccubeModule($code)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();

        return $objQuery->delete('dtb_module', 'module_code = ?', array($code));
    }

    protected function getEccubeModule($code)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();

        return $objQuery->getRow('*', 'dtb_module', 'module_code = ?', array($code));
    }

    protected function getEccubeModules()
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->order = 'ORDER BY module_id';

        return $objQuery->select('*', 'dtb_module', 'module_id != 0');
    }
}
