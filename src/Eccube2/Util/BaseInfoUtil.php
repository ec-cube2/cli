<?php

namespace Eccube2\Util;

class BaseInfoUtil
{
    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストールされていません.');
        }
    }

    /**
     * @return array
     */
    public function get()
    {
        $objDb = new \SC_Helper_DB_Ex();

        return $objDb->sfGetBasisData(true);
    }

    /**
     * @param array $arrData
     */
    public function set($arrData)
    {
        \SC_Helper_DB_Ex::registerBasisData($arrData);
    }

    /**
     * @return bool
     */
    public function exists()
    {
        $objDb = new \SC_Helper_DB_Ex();

        return $objDb->sfGetBasisExists();
    }

    /**
     * @return bool
     */
    public function createCache()
    {
        $objDb = new \SC_Helper_DB_Ex();

        return $objDb->sfCreateBasisDataCache();
    }

    public function clearCache()
    {
        $filepath = MASTER_DATA_REALDIR . 'dtb_baseinfo.serial';
        if (is_file($filepath)) {
            unlink($filepath);
        }
    }
}
