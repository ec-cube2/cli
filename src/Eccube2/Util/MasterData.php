<?php

namespace Eccube2\Util;

class MasterData
{
    /** @var \LC_Page_Admin_System_Masterdata_Ex $page */
    protected $page;

    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストールされていません.');
        }

        require_once CLASS_EX_REALDIR . 'page_extends/admin/system/LC_Page_Admin_System_Masterdata_Ex.php';
        $this->page = new \LC_Page_Admin_System_Masterdata_Ex();
    }

    public function clearCache($name)
    {
        $masterData = new \SC_DB_MasterData_Ex();
        $masterData->clearCache($name);
    }

    public function clearAllCache()
    {
        $names = $this->getNames();

        $masterData = new \SC_DB_MasterData_Ex();
        foreach ($names as $name) {
            $masterData->clearCache($name);
        }
    }

    /**
     * @return array
     */
    public function getNames()
    {
        return $this->page->getMasterDataNames(array('mtb_pref', 'mtb_zip', 'mtb_constants'));
    }

    /**
     * @param $name
     * @return array
     */
    public function get($name)
    {
        $masterData = new \SC_DB_MasterData_Ex();

        return $masterData->getMasterData($name);
    }
}
