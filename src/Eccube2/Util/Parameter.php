<?php


namespace Eccube2\Util;


class Parameter
{
    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストールされていません.');
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param bool $createCache
     */
    public function set($key, $value, $createCache = true)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();

        $objQuery->update('mtb_constants', array(
            'name' => $value,
        ), 'id = ?', array($key));

        if ($createCache) {
            $this->createCache();
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param string $comment
     * @param bool $createCache
     */
    public function add($key, $value, $comment, $createCache = true)
    {
        $masterData = new \SC_DB_MasterData_Ex();
        $masterData->insertMasterData('mtb_constants', $key, $value, $comment);

        if ($createCache) {
            $this->createCache();
        }
    }

    /**
     * @param string $key
     * @return array
     */
    public function get($key)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();

        return $objQuery->get('name', 'mtb_constants', 'id = ?', array($key));
    }

    public function getAll()
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->setOrder('rank');

        return $objQuery->select('*', 'mtb_constants');
    }

    public function clearCache()
    {
        $masterData = new \SC_DB_MasterData_Ex();
        $masterData->clearCache('mtb_constants');
    }

    public function createCache()
    {
        $masterData = new \SC_DB_MasterData_Ex();
        $masterData->createCache('mtb_constants', array(), true, array('id', 'remarks'));
    }
}
