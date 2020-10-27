<?php

namespace Eccube2\Util;

class Plugin
{
    /** @var \LC_Page_Admin_OwnersStore_Ex $page */
    protected $page;

    protected $dir;

    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストールされていません.');
        }

        $this->dir = rtrim(PLUGIN_UPLOAD_REALDIR, '/');

        require_once CLASS_EX_REALDIR . 'page_extends/admin/ownersstore/LC_Page_Admin_OwnersStore_Ex.php';
        $this->page = new \LC_Page_Admin_OwnersStore_Ex();
    }

    /**
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * @param $code
     * @return string
     */
    public function getTargetDir($code)
    {
        return $this->dir.'/'.$code;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function isInstalled($code)
    {
        return $this->page->isInstalledPlugin($code);
    }

    /**
     * @param string $code
     * @return array
     */
    public function install($code)
    {
        // install
        $plugin_dir_path = $this->getTargetDir($code) . '/';
        if (!is_dir($plugin_dir_path)) {
            throw new \Exception($code . ' は存在しません。');
        }

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
            return $this->getErrors($arrErr);
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
            return $this->getErrors($arrErr);
        }

        // 既にインストールされていないかを判定.
        if ($this->page->isInstalledPlugin($arrPluginInfo['PLUGIN_CODE']) === true) {
            $arrErr['plugin_file'] = '※ ' . $arrPluginInfo['PLUGIN_NAME'] . 'は既にインストールされています。<br/>';

            return $this->getErrors($arrErr);
        }

        // プラグイン情報をDB登録
        if ($this->page->registerData($arrPluginInfo) === false) {
            $arrErr['plugin_file'] = '※ DB登録に失敗しました。<br/>';

            return $this->getErrors($arrErr);
        }

        // プラグイン情報を取得
        $plugin = $this->findOneByCode($arrPluginInfo['PLUGIN_CODE']);

        // クラスファイルを読み込み.
        $plugin_class_file_path = $this->page->getPluginFilePath($plugin['plugin_code'], $plugin['class_name']);
        $arrErr = $this->page->requirePluginFile($plugin_class_file_path, $key);
        if ($this->page->isError($arrErr) === true) {
            return $this->getErrors($arrErr);
        }

        // プラグインhtmlディレクトリ作成
        $plugin_html_dir_path = $this->page->getHtmlPluginDir($plugin['plugin_code']);
        $this->page->makeDir($plugin_html_dir_path);

        $arrErr = $this->page->execPlugin($plugin, $plugin['class_name'], 'install');
        if ($this->page->isError($arrErr) === true) {
            // エラー時, transactionがabortしてるのでロールバック
            $objQuery->rollback();

            return $this->getErrors($arrErr);
        }

        $objQuery->commit();

        // 不要なファイルの削除
        \SC_Helper_FileManager_Ex::deleteFile(DOWNLOADS_TEMP_PLUGIN_INSTALL_DIR, false);

        return $this->getErrors($arrErr);
    }

    /**
     * @param string $code
     * @return array
     */
    public function update($code)
    {
        // update
        $target_plugin = $this->findOneByCode($code);
        $plugin_dir_path = $this->getTargetDir($code) . '/';

        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->begin();

        // アップデート前に不要なファイルを消しておきます.
        $this->page->makeDir(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR);
        \SC_Helper_FileManager_Ex::deleteFile(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR, false);
        \SC_Utils_Ex::copyDirectory($plugin_dir_path, DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR);

        // plugin_infoを読み込み.
        $arrErr = $this->page->requirePluginFile(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR . 'plugin_info.php', $target_plugin['plugin_code']);
        if ($this->page->isError($arrErr) === true) {
            return $this->getErrors($arrErr);
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

            return $this->getErrors($arrErr);
        }

        // plugin_update.phpを読み込み.
        $arrErr = $this->page->requirePluginFile(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR . 'plugin_update.php', $target_plugin['plugin_code']);
        if ($this->page->isError($arrErr) === true) {
            return $this->getErrors($arrErr);
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

            return $this->getErrors($arrErr);
        }

        $objQuery->commit();

        // 保存ディレクトリの削除.
        \SC_Helper_FileManager_Ex::deleteFile(DOWNLOADS_TEMP_PLUGIN_UPDATE_DIR, false);

        return $this->getErrors($arrErr);
    }

    /**
     * @param string $code
     * @return array
     */
    public function uninstall($code)
    {
        $plugin = $this->findOneByCode($code);

        return $this->page->uninstallPlugin($plugin);
    }

    /**
     * @param string $code
     * @return array
     */
    public function enable($code)
    {
        $plugin = $this->findOneByCode($code);

        return $this->page->enablePlugin($plugin);
    }

    /**
     * @param string $code
     * @return array
     */
    public function disable($code)
    {
        $plugin = $this->findOneByCode($code);

        return $this->page->disablePlugin($plugin);
    }

    /**
     * @param int $id
     * @return array
     */
    public function get($id)
    {
        return \SC_Plugin_Util_Ex::getPluginByPluginId($id);
    }

    /**
     * @param string $code
     * @return array
     */
    public function findOneByCode($code)
    {
        return \SC_Plugin_Util_Ex::getPluginByPluginCode($code);
    }

    /**
     * @return array|null
     */
    public function getAll()
    {
        return \SC_Plugin_Util_Ex::getAllPlugin();
    }

    /**
     * @param array $errors
     * @return bool
     */
    private function isError($errors)
    {
        return $this->page->isError($errors);
    }

    /**
     * @param array $errors
     * @return array|null
     */
    private function getErrors($errors)
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
