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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BasePluginCommand extends Command
{
    /** @var \LC_Page_Admin_OwnersStore_Ex $page */
    protected $page;

    protected $pluginDir;

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $baseDir = __DIR__.'/../../../../../../';
        require_once $baseDir . '/html/require.php';

        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストール後にプラグインを追加してください.');
        }

        $this->pluginDir = rtrim(PLUGIN_UPLOAD_REALDIR, '/');

        require_once CLASS_EX_REALDIR . 'page_extends/admin/ownersstore/LC_Page_Admin_OwnersStore_Ex.php';
        $this->page = new \LC_Page_Admin_OwnersStore_Ex();
    }

    /**
     * {@inheritDoc}
     */
    public function getInstallPath($code)
    {
        return $this->pluginDir.'/'.$code;
    }

    public function isInstalled($code)
    {
        return $this->page->isInstalledPlugin($code);
    }

    public function listPlugin(InputInterface $input, OutputInterface $output)
    {
        $plugins = $this->getEccubePlugins();

        $rows = array();
        foreach ($plugins as $plugin) {
            $rows[] = array(
                $plugin['plugin_name'],
                $plugin['plugin_code'],
                $plugin['enable'] ? 'o' : 'x',
            );
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('プラグイン一覧');
        $io->table(array('Code', 'Name', 'Enable'), $rows);
    }

    public function info(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('plugin_name');

        $plugin = $this->getEccubePlugin($code);
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
    }

    /**
     * {@inheritDoc}
     */
    public function install(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('plugin_name');

        $plugin_status = PLUGIN_ENABLE_FALSE;
        if (!$this->isInstalled($code)) {
            $errors = $this->installEccubePlugin($code);
        } else {
            $plugin = $this->getEccubePlugin($code);
            $plugin_status = $plugin['enable'];

            $errors = $this->updateEccubePlugin($code);
        }

        if ($errors) {
            foreach ($errors as $error) {
                $output->writeln('    <error>'.$error.'</error>');
            }
        } else {
            $output->writeln('    <info>インストールしました</info>');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('plugin_name');

        if (!$this->isInstalled($code)) {
            throw new \InvalidArgumentException($code.' はインストールされていません。');
        }

        $errors = $this->updateEccubePlugin($code);
        if ($errors) {
            foreach ($errors as $error) {
                $output->writeln('    <error>'.$error.'</error>');
            }
        } else {
            $output->writeln('    <info>アップデートしました</info>');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function enable(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('plugin_name');

        $errors = $this->enableEccubePlugin($code);
        if ($errors) {
            foreach ($errors as $error) {
                $output->writeln('    <error>'.$error.'</error>');
            }
        } else {
            $output->writeln('    <info>有効にしました</info>');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function disable(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('plugin_name');

        if (!$this->isInstalled($code)) {
            throw new \InvalidArgumentException($code.' はインストールされていません。');
        }

        $errors = $this->disableEccubePlugin($code);
        if ($errors) {
            foreach ($errors as $error) {
                $output->writeln('    <error>'.$error.'</error>');
            }
        } else {
            $output->writeln('    <info>無効にしました</info>');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InputInterface $input, OutputInterface $output)
    {
        $code = $input->getArgument('plugin_name');

        if (!$this->isInstalled($code)) {
            throw new \InvalidArgumentException($code.' はインストールされていません。');
        }

        $output->write('<info>'.$code.' をアンインストールしました</info>');
    }

    protected function installEccubePlugin($code)
    {
        // install
        $plugin_dir_path = $this->getInstallPath($code) . '/';
        $key = $code;

        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->begin();

        // 一時展開ディレクトリにファイルがある場合は事前に削除.
        $this->page->makeDir(DOWNLOADS_TEMP_PLUGIN_INSTALL_DIR);
        $arrFileHash = \SC_Helper_FileManager_Ex::sfGetFileList(DOWNLOADS_TEMP_PLUGIN_INSTALL_DIR);
        if (count($arrFileHash) > 0) {
            \SC_Helper_FileManager_Ex::deleteFile(DOWNLOADS_TEMP_PLUGIN_INSTALL_DIR, false);
        }
        \SC_Utils_Ex::copyDirectory($plugin_dir_path, DOWNLOADS_TEMP_PLUGIN_INSTALL_DIR);

        // plugin_infoを読み込み.
        $arrErr = $this->page->requirePluginFile(DOWNLOADS_TEMP_PLUGIN_INSTALL_DIR . 'plugin_info.php', $key);
        if ($this->page->isError($arrErr) === true) {
            return $this->checkErrors($arrErr);
        }

        //
        $plugin_info = '?>' . file_get_contents(DOWNLOADS_TEMP_PLUGIN_INSTALL_DIR . 'plugin_info.php');
        $plugin_info = str_replace('class plugin_info', 'class plugin_info_' . $key, $plugin_info);
        eval($plugin_info);

        // リフレクションオブジェクトを生成.
        $objReflection = new \ReflectionClass('plugin_info_' . $key);
        $arrPluginInfo = $this->page->getPluginInfo($objReflection);
        // プラグインクラスに必須となるパラメータが正常に定義されているかチェックします.
        $arrErr = $this->page->checkPluginConstants($objReflection, DOWNLOADS_TEMP_PLUGIN_INSTALL_DIR);
        if ($this->page->isError($arrErr) === true) {
            return $this->checkErrors($arrErr);
        }

        // 既にインストールされていないかを判定.
        if ($this->page->isInstalledPlugin($arrPluginInfo['PLUGIN_CODE']) === true) {
            $arrErr['plugin_file'] = '※ ' . $arrPluginInfo['PLUGIN_NAME'] . 'は既にインストールされています。<br/>';

            return $this->checkErrors($arrErr);
        }

        // プラグイン情報をDB登録
        if ($this->page->registerData($arrPluginInfo) === false) {
            $arrErr['plugin_file'] = '※ DB登録に失敗しました。<br/>';

            return $this->checkErrors($arrErr);
        }

        // プラグイン情報を取得
        $plugin = \SC_Plugin_Util_Ex::getPluginByPluginCode($arrPluginInfo['PLUGIN_CODE']);

        // クラスファイルを読み込み.
        $plugin_class_file_path = $this->page->getPluginFilePath($plugin['plugin_code'], $plugin['class_name']);
        $arrErr = $this->page->requirePluginFile($plugin_class_file_path, $key);
        if ($this->page->isError($arrErr) === true) {
            return $this->checkErrors($arrErr);
        }

        // プラグインhtmlディレクトリ作成
        $plugin_html_dir_path = $this->page->getHtmlPluginDir($plugin['plugin_code']);
        $this->page->makeDir($plugin_html_dir_path);

        $arrErr = $this->page->execPlugin($plugin, $plugin['class_name'], 'install');
        if ($this->page->isError($arrErr) === true) {
            // エラー時, transactionがabortしてるのでロールバック
            $objQuery->rollback();

            return $this->checkErrors($arrErr);
        }

        $objQuery->commit();

        // 不要なファイルの削除
        \SC_Helper_FileManager_Ex::deleteFile(DOWNLOADS_TEMP_PLUGIN_INSTALL_DIR, false);

        return $this->checkErrors($arrErr);
    }

    protected function updateEccubePlugin($code)
    {
        // update
        $target_plugin = $this->getEccubePlugin($code);
        $plugin_dir_path = $this->getInstallPath($code) . '/';

        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->begin();

        // アップデート前に不要なファイルを消しておきます.
        $this->page->makeDir(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR);
        \SC_Helper_FileManager_Ex::deleteFile(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR, false);
        \SC_Utils_Ex::copyDirectory($plugin_dir_path, DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR);

        // plugin_infoを読み込み.
        $arrErr = $this->page->requirePluginFile(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR . 'plugin_info.php', $target_plugin['plugin_code']);
        if ($this->page->isError($arrErr) === true) {
            return $this->checkErrors($arrErr);
        }

        //
        $plugin_info = '?>' . file_get_contents(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR . 'plugin_info.php');
        $plugin_info = str_replace('class plugin_info', 'class plugin_info_' . $target_plugin['plugin_code'], $plugin_info);
        eval($plugin_info);

        // リフレクションオブジェクトを生成.
        $objReflection = new \ReflectionClass('plugin_info_' . $target_plugin['plugin_code']);
        $arrPluginInfo = $this->page->getPluginInfo($objReflection);
        if ($arrPluginInfo['PLUGIN_CODE'] != $target_plugin['plugin_code']) {
            $arrErr[$target_plugin['plugin_code']] = '※ プラグインコードが一致しません。 ' . $arrPluginInfo['PLUGIN_CODE'] . ':' . $target_plugin['plugin_code'] . '<br/>';

            return $this->checkErrors($arrErr);
        }

        // plugin_update.phpを読み込み.
        $arrErr = $this->page->requirePluginFile(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR . 'plugin_update.php', $target_plugin['plugin_code']);
        if ($this->page->isError($arrErr) === true) {
            return $this->checkErrors($arrErr);
        }

        //
        $plugin_update = '?>' . file_get_contents(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR . 'plugin_update.php');
        $plugin_update = str_replace('class plugin_update', 'class plugin_update_' . $target_plugin['plugin_code'], $plugin_update);
        eval($plugin_update);

        // プラグインクラスファイルのUPDATE処理を実行.
        $arrErr = $this->page->execPlugin($target_plugin, 'plugin_update_' . $target_plugin['plugin_code'], 'update');

        // プラグイン情報を更新
        if ($this->page->registerData($arrPluginInfo, 'update') === false) {
            $arrErr['plugin_file'] = '※ プラグイン情報の更新に失敗しました。<br/>';

            return $this->checkErrors($arrErr);
        }

        $objQuery->commit();

        // 保存ディレクトリの削除.
        \SC_Helper_FileManager_Ex::deleteFile(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR, false);

        return $this->checkErrors($arrErr);
    }

    protected function uninstallEccubePlugin($code)
    {
        $plugin = $this->getEccubePlugin($code);

        return $this->page->uninstallPlugin($plugin);
    }

    protected function enableEccubePlugin($code)
    {
        $plugin = $this->getEccubePlugin($code);

        return $this->page->enablePlugin($plugin);
    }

    protected function disableEccubePlugin($code)
    {
        $plugin = $this->getEccubePlugin($code);

        return $this->page->disablePlugin($plugin);
    }

    protected function getEccubePlugin($code)
    {
        return \SC_Plugin_Util_Ex::getPluginByPluginCode($code);
    }

    protected function getEccubePlugins()
    {
        return \SC_Plugin_Util_Ex::getAllPlugin();
    }

    protected function checkErrors($errors)
    {
        $errors = array();

        if ($this->page->isError($errors)) {
            foreach ($errors as $error) {
                $disp_error = str_replace(array('<br>', '<br/>', '<br />', '※ '), '', $error);
                $errors[] = $disp_error;
            }

            return $errors;
        }

        return null;
    }
}
