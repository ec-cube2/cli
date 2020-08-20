<?php

namespace Eccube2\Util;

class System
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
    public function getAll()
    {
        return array(
            'EC-CUBE' => $this->getECCUBE(),
            'OS' => $this->getOS(),
            'PHP' => $this->getPHP(),
            'DB' => $this->getDB(),
        );
    }

    /**
     * @return array
     */
    public function getECCUBE()
    {
        return array(
            'EC-CUBEバージョン' => ECCUBE_VERSION,
        );
    }

    /**
     * @return array
     */
    public function getOS()
    {
        return array(
            'オペレーティングシステム' => php_uname('s'),
            'ホスト名' => php_uname('n'),
            'リリース名' => php_uname('r'),
            'バージョン情報' => php_uname('v'),
            'マシン型' => php_uname('m'),
        );
    }

    /**
     * @return array
     */
    public function getPHP()
    {
        return array(
            'PHPバージョン' => $this->getPHPVersion(),
            'PHPモジュール' => $this->getPHPModules(),
        );
    }

    /**
     * @return array
     */
    public function getDB()
    {
        return array(
            'DBバージョン' => $this->getDBVersion(),
        );
    }

    /**
     * @return string
     */
    public function getDBVersion()
    {
        $dbFactory = \SC_DB_DBFactory_Ex::getInstance();

        return $dbFactory->sfGetDBVersion();
    }

    /**
     * @return string
     */
    public function getPHPVersion()
    {
        return phpversion();
    }

    /**
     * @return array
     */
    public function getPHPModules()
    {
        return get_loaded_extensions();
    }
}
