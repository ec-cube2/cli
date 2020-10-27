<?php

namespace Eccube2\Util;

class BackupUtil
{
    /** @var \LC_Page_Admin_System_Bkup_Ex $page */
    protected $page;

    protected $ext;

    protected $directory;

    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストールされていません.');
        }

        $this->ext = '.tar.gz';
        $this->directory = DATA_REALDIR . 'downloads/backup/';
        if (defined('BACKUP_REALDIR')) {
            $this->directory = BACKUP_REALDIR;
        }

        require_once CLASS_EX_REALDIR . 'page_extends/admin/system/LC_Page_Admin_System_Bkup_Ex.php';
        $this->page = new \LC_Page_Admin_System_Bkup_Ex();
        $this->page->bkup_ext = $this->ext;
        $this->page->bkup_dir = $this->directory;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->page->lfGetBkupData('ORDER BY create_date DESC');
    }

    /**
     * @param string $name
     * @return array
     */
    public function findByName($name)
    {
        return $this->page->lfGetBkupData('', $name);
    }

    /**
     * DBにデータ追加
     *
     * @param string $name
     * @param string $memo
     */
    public function add($name, $memo)
    {
        $this->page->lfUpdBkupData(array(
            'bkup_name' => $name,
            'bkup_memo' => $memo,
        ));
    }

    /**
     * @param string $name
     * @param bool $force
     * @throws \Exception
     */
    public function restore($name, $force = false)
    {
        $mode = $force ? 'restore_config' : 'restore';
        $success = $this->page->lfRestore($name, $this->directory, $this->ext, $mode);

        if (!$success) {
            throw new \Exception('リストアに失敗しました。');
        }
    }

    /**
     * バックアップ削除
     * @param $name
     */
    public function delete($name)
    {
        $arrForm = array(
            'list_name' => $name,
        );

        $this->page->lfDeleteBackUp($arrForm, $this->directory, $this->ext);
    }

    /**
     * @param string $name
     * @param string $memo
     * @throws \Exception
     */
    public function create($name, $memo = '')
    {
        $workDir = $this->directory . $name . '/';

        // $workDir の事前削除
        \SC_Helper_FileManager_Ex::deleteFile($workDir);

        // バックアップファイル作成
        $result = $this->page->lfCreateBkupData($name, $workDir);

        // $workDir の削除
        \SC_Helper_FileManager_Ex::deleteFile($workDir);

        if ($result !== true) {
            throw new \Exception('バックアップに失敗しました。(' . $result . ')');
        }

        $this->add($name, $memo);
    }
}
