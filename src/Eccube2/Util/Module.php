<?php

namespace Eccube2\Util;

class Module
{
    protected $dir;

    protected $configUrlBase;

    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストール後にプラグインを追加してください.');
        }

        $this->dir = rtrim(MODULE_REALDIR, '/');
        $this->configUrlBase = HTTPS_URL.ADMIN_DIR.'load_module_config.php?module_id=';
    }

    /**
     * @return string
     */
    public function getDir()
    {
        return $this->dir;
    }

    /**
     * @param array $arrModule
     * @return string|null
     */
    public function getConfigUrl($arrModule)
    {
        if (empty($arrModule)) {
            return null;
        }

        return $this->configUrlBase.$arrModule['module_id'];
    }

    /**
     * @param int $id
     * @return string
     */
    public function getConfigUrlById($id)
    {
        $arrModule = $this->get($id);

        return $this->getConfigUrl($arrModule);
    }

    /**
     * @param string $code
     * @return string|null
     */
    public function getConfigUrlByCode($code)
    {
        $arrModule = $this->findOneByCode($code);

        return $this->getConfigUrl($arrModule);
    }

    /**
     * @param string $code
     * @return bool
     */
    public function isInstalled($code)
    {
        $eccubeModule = $this->findOneByCode($code);

        return !empty($eccubeModule) ? true : false;
    }

    /**
     * @param string $code
     * @param int|null $id
     * @return int
     */
    public function install($code, $id = null)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        if ($id === null) {
            $id = max($objQuery->max('module_id', 'dtb_module'), 1000000) + 1;
        }

        $arrModule = array(
            'module_id' => $id,
            'module_code' => $code,
            'module_name' => '',
            'auto_update_flg' => '0',
            'create_date' => 'CURRENT_TIMESTAMP',
            'update_date' => 'CURRENT_TIMESTAMP'
        );
        $objQuery->insert('dtb_module', $arrModule, 'module_code = ?', array($code));

        return $arrModule['module_id'];
    }

    /**
     * @param string $code
     * @return int
     */
    public function update($code)
    {
        $arrModule = $this->findOneByCode($code);
        $arrUpdate = array(
            'module_code' => $code,
            'module_name' => '',
            'update_date' => 'CURRENT_TIMESTAMP'
        );
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->update('dtb_module', $arrUpdate, 'module_id = ?', array($arrModule['module_id']));

        return $arrModule['module_id'];
    }

    /**
     * @param string $code
     * @return array
     */
    public function uninstall($code)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();

        return $objQuery->delete('dtb_module', 'module_code = ?', array($code));
    }

    /**
     * @param int $id
     * @return array
     */
    public function get($id)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();

        return $objQuery->getRow('*', 'dtb_module', 'module_id = ?', array($id));
    }

    /**
     * @param string $code
     * @return array
     */
    public function findOneByCode($code)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();

        return $objQuery->getRow('*', 'dtb_module', 'module_code = ?', array($code));
    }

    /**
     * @return array|null
     */
    public function getAll()
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->order = 'ORDER BY module_id';

        return $objQuery->select('*', 'dtb_module', 'module_id != 0');
    }
}
