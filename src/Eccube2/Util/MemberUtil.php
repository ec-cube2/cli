<?php

namespace Eccube2\Util;

class MemberUtil
{
    /** @var \LC_Page_Admin_System_Ex $page */
    protected $page;

    /** @var \LC_Page_Admin_System_Input_Ex $pageInput */
    protected $pageInput;

    /** @var \LC_Page_Admin_System_Delete_Ex $pageDelete */
    protected $pageDelete;

    protected $ext;

    protected $directory;

    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストールされていません.');
        }

        require_once CLASS_EX_REALDIR . 'page_extends/admin/system/LC_Page_Admin_System_Ex.php';
        $this->page = new \LC_Page_Admin_System_Ex();

        require_once CLASS_EX_REALDIR . 'page_extends/admin/system/LC_Page_Admin_System_Input_Ex.php';
        $this->pageInput = new \LC_Page_Admin_System_Input_Ex();

        require_once CLASS_EX_REALDIR . 'page_extends/admin/system/LC_Page_Admin_System_Delete_Ex.php';
        $this->pageDelete = new \LC_Page_Admin_System_Delete_Ex();

        $masterData = new \SC_DB_MasterData_Ex();
        $this->arrAUTHORITY = $masterData->getMasterData('mtb_authority');
        $this->arrWORK = $masterData->getMasterData('mtb_work');
    }

    /**
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function get($id)
    {
        $member = $this->pageInput->getMemberData($id);

        if (empty($member)) {
            throw new \Exception('メンバーが見つかりません.');
        }

        return $member;
    }

    /**
     * @param string $loginId
     * @return int|null
     * @throws \Exception
     */
    public function getIdByLoginId($loginId)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();

        $id = $objQuery->get('member_id', 'dtb_member', 'del_flg <> 1 AND member_id <> ' . ADMIN_ID . ' AND login_id = ?', array($loginId));

        if ($id === null) {
            throw new \Exception('メンバーが見つかりません.');
        }

        return (int) $id;
    }

    /**
     * @param string $loginId
     * @return array
     * @throws \Exception
     */
    public function findOneByLoginId($loginId)
    {
        $id = $this->getIdByLoginId($loginId);

        return $this->get($id);
    }

    /**
     * @param int $start 開始位置
     * @return array
     */
    public function getList($start = 0)
    {
        return $this->page->getMemberData($start);
    }

    /**
     * @param string $where
     * @param string $val
     * @return bool
     */
    public function exests($where, $val)
    {
        return $this->pageInput->memberDataExists($where, $val);
    }

    /**
     * @param string $loginId
     * @throws \Exception
     */
    public function deleteByLoginId($loginId)
    {
        $id = $this->getIdByLoginId($loginId);

        $this->delete($id);
    }

    /**
     * @param array $member
     */
    public function create($member)
    {
        $_SESSION['member_id'] = ADMIN_ID;
        $this->pageInput->insertMemberData($member);
    }

    /**
     * @param int $id
     * @param array $member
     */
    public function update($id, $member)
    {
        $this->pageInput->updateMemberData($id, $member);
    }

    /**
     * @param int $id
     */
    public function delete($id)
    {
        $this->pageDelete->deleteMember($id);
    }

    /**
     * @param string $loginId
     * @param string $password
     * @throws \Exception
     */
    public function setPasswordByLoginId($loginId, $password)
    {
        $id = $this->getIdByLoginId($loginId);
        $member = $this->get($id);
        $member['password'] = $password;

        $this->update($id, $member);
    }

    /**
     * @param string $loginId
     * @param int $work
     * @throws \Exception
     */
    public function setWorkByLoginId($loginId, $work)
    {
        $id = $this->getIdByLoginId($loginId);
        $member = $this->get($id);
        $member['password'] = DEFAULT_PASSWORD;
        $member['work'] = $work;

        $this->update($id, $member);
    }
}
