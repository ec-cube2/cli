<?php

namespace Eccube2\Util;

class Template
{
    /** @var \LC_Page_Admin_Design_Template_Ex $page */
    protected $page;

    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストール後にプラグインを追加してください.');
        }

        require_once CLASS_EX_REALDIR . 'page_extends/admin/design/LC_Page_Admin_Design_Template_Ex.php';
        $this->page = new \LC_Page_Admin_Design_Template_Ex();
    }

    /**
     * @return string
     */
    public function getPcTemplate()
    {
        return $this->getTempate(DEVICE_TYPE_PC);
    }

    /**
     * @return string
     */
    public function getSmartphoneTemplate()
    {
        return $this->getTempate(DEVICE_TYPE_SMARTPHONE);
    }

    /**
     * @return string
     */
    public function getMobileTemplate()
    {
        return $this->getTempate(DEVICE_TYPE_MOBILE);
    }

    /**
     * @param int $deviceTypeId
     * @return string
     */
    public function getTempate($deviceTypeId)
    {
        return $this->page->getTemplateName($deviceTypeId);
    }

    /**
     * @param string $templateCode
     */
    public function setPcTemplate($templateCode)
    {
        $this->setTempate(DEVICE_TYPE_PC, $templateCode);
    }

    /**
     * @param string $templateCode
     */
    public function setSmartphoneTemplate($templateCode)
    {
        $this->setTempate(DEVICE_TYPE_SMARTPHONE, $templateCode);
    }

    /**
     * @param string $templateCode
     */
    public function setMobileTemplate($templateCode)
    {
        $this->setTempate(DEVICE_TYPE_MOBILE, $templateCode);
    }

    /**
     * @param int $deviceTypeId
     * @param string $templateCode
     */
    public function setTempate($deviceTypeId, $templateCode)
    {
        $this->page->doUpdateMasterData($templateCode, $deviceTypeId);
    }

    /**
     *
     */
    public function clearAllCache()
    {
        $codes = array(
            'admin',
            TEMPLATE_NAME,
            SMARTPHONE_TEMPLATE_NAME,
            MOBILE_TEMPLATE_NAME,
        );

        foreach ($codes as $code) {
            $this->clearCache($code);
        }
    }

    /**
     * @param string $code
     * @return bool
     */
    public function clearCache($code)
    {
        $templatesCDir = realpath(COMPILE_REALDIR . '../'). $code. '/';

        if (\SC_Helper_FileManager_Ex::deleteFile($templatesCDir) === false) {
            return false;
        }

        return true;
    }
}
