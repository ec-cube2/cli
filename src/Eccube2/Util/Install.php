<?php

namespace Eccube2\Util;

class Install
{
    public $sequences = array(
        array('dtb_best_products', 'best_id'),
        array('dtb_bloc', 'bloc_id'),
        array('dtb_category', 'category_id'),
        array('dtb_class', 'class_id'),
        array('dtb_classcategory', 'classcategory_id'),
        array('dtb_csv', 'no'),
        array('dtb_csv_sql', 'sql_id'),
        array('dtb_customer', 'customer_id'),
        array('dtb_deliv', 'deliv_id'),
        array('dtb_holiday', 'holiday_id'),
        array('dtb_kiyaku', 'kiyaku_id'),
        array('dtb_mail_history', 'send_id'),
        array('dtb_maker', 'maker_id'),
        array('dtb_member', 'member_id'),
        array('dtb_module_update_logs', 'log_id'),
        array('dtb_news', 'news_id'),
        array('dtb_order', 'order_id'),
        array('dtb_order_detail', 'order_detail_id'),
        array('dtb_other_deliv', 'other_deliv_id'),
        array('dtb_pagelayout', 'page_id'),
        array('dtb_payment', 'payment_id'),
        array('dtb_products_class', 'product_class_id'),
        array('dtb_products', 'product_id'),
        array('dtb_review', 'review_id'),
        array('dtb_send_history', 'send_id'),
        array('dtb_mailmaga_template', 'template_id'),
        array('dtb_plugin', 'plugin_id'),
        array('dtb_plugin_hookpoint', 'plugin_hookpoint_id'),
        array('dtb_api_config', 'api_config_id'),
        array('dtb_api_account', 'api_account_id'),
        array('dtb_tax_rule', 'tax_rule_id'),
    );

    public function __construct()
    {
        if (is_dir(HTML_REALDIR . 'install/')) {
            define('INSTALL_REALDIR', HTML_REALDIR . 'install/');
        } elseif (is_dir(DATA_REALDIR . '../html/install/')) {
            define('INSTALL_REALDIR', DATA_REALDIR . '../html/install/');
        } else {
            throw new \Exception('INSTALL_REALDIR を設定してください。');
        }
        define('INSTALL_INFO_URL', 'http://www.ec-cube.net/install_info/index.php');
        define('DEFAULT_COUNTRY_ID', 392);
    }

    public function agreement()
    {
        $html = file_get_contents(INSTALL_REALDIR . 'templates/agreement.tpl');
        $matches = array();
        preg_match('|<div id="agreement">(.+?)</div>|s', $html, $matches);
        $agreement = $matches[1];
        $agreement = preg_replace('|^\s+|m', '', $agreement);
        $agreement = preg_replace('|\n+|', '', $agreement);
        $agreement = preg_replace('|<br/?>|', "\n", $agreement);

        return $agreement;
    }

    public function config()
    {

    }

    /**
     * @throws \Exception
     */
    public function createTable()
    {
        $this->executeSQL(INSTALL_REALDIR . 'sql/create_table_' . DB_TYPE . '.sql');
    }

    /**
     * @throws \Exception
     */
    public function insertData()
    {
        $this->executeSQL(INSTALL_REALDIR . 'sql/insert_data.sql');
    }

    /**
     * @throws \Exception
     */
    public function dropTable()
    {
        $this->executeSQL(INSTALL_REALDIR . 'sql/drop_table.sql');
    }

    /**
     * @return bool|string
     * @throws \Exception
     */
    public function copyImage()
    {
        return \SC_Utils_Ex::sfCopyDir(INSTALL_REALDIR . 'save_image/', HTML_REALDIR . 'upload/save_image/');
    }

    public function createDirectory($umask = 0)
    {
        $paths = array(
            DATA_REALDIR . 'downloads/plugin',
            HTML_REALDIR . 'plugin',
            HTML_REALDIR . 'upload/temp_plugin',
            DATA_REALDIR . 'downloads/tmp',
            DATA_REALDIR . 'downloads/tmp/plugin_install',
            HTML_REALDIR . 'upload/temp_template',
            HTML_REALDIR . 'upload/save_image',
            HTML_REALDIR . 'upload/temp_image',
            HTML_REALDIR . 'upload/graph_image',
            HTML_REALDIR . 'upload/mobile_image',
            DATA_REALDIR . 'downloads/module',
            DATA_REALDIR . 'downloads/update',
            DATA_REALDIR . 'upload/csv',
        );

        umask($umask);
        foreach ($paths as $path) {
            if (!file_exists($path)) {
                mkdir($path);
            }
        }
    }

    /**
     * @param $filepath
     * @param $arrDsn
     * @throws \Exception
     */
    public function executeSQL($filepath)
    {
        if (!file_exists($filepath)) {
            throw new \Exception('スクリプトファイルが見つかりません');
        }

        if ($fp = fopen($filepath, 'r')) {
            $sql = fread($fp, filesize($filepath));
            fclose($fp);
        }

        $objQuery = \SC_Query_Ex::getSingletonInstance();
        /** @var \MDB2_Driver_Common $objDB */
        $objDB = $objQuery->conn;

        $sql_split = split(';', $sql);
        foreach ($sql_split as $key => $val) {
            if (trim($val) === '') {
                continue;
            }

            $objDB->query($val);
        }
    }

    /**
     * @throws \Exception
     */
    public function createSequence()
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        /** @var \MDB2_Driver_Common $objDB */
        $objDB = $objQuery->conn;
        /** @var \MDB2_Driver_Manager_Common $objManager */
        $objManager = $objDB->loadModule('Manager');

        $exists = $objManager->listSequences();
        foreach ($this->sequences as $seq) {
            $max = $objQuery->max($seq[1], $seq[0]);

            $seq_name = $seq[0] . '_' . $seq[1];
            if (!in_array($seq_name, $exists)) {
                $result = $objManager->createSequence($seq_name, $max + 1);

                if (\PEAR::isError($result)) {
                    throw new \Exception($result->message);
                }
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function dropSequence()
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        /** @var \MDB2_Driver_Common $objDB */
        $objDB = $objQuery->conn;
        /** @var \MDB2_Driver_Manager_Common $objManager */
        $objManager = $objDB->loadModule('Manager');

        $exists = $objManager->listSequences();
        foreach ($this->sequences as $seq) {
            $seq_name = $seq[0] . '_' . $seq[1];
            if (in_array($seq_name, $exists)) {
                $result = $objManager->dropSequence($seq_name);

                if (\PEAR::isError($result)) {
                    throw new \Exception($result->message);
                }
            }
        }
    }

    /**
     * @param string $adminDir
     * @throws \Exception
     */
    public function renameAdminDirectory($adminDir)
    {
        if (!defined('ADMIN_DIR')) {
            define('ADMIN_DIR', 'admin/');
        }

        $oldAdminDir = \SC_Utils_Ex::sfTrimURL(ADMIN_DIR);
        if ($adminDir === $oldAdminDir) {
            return;
        }

        if (file_exists(HTML_REALDIR . $adminDir)) {
            throw new \Exception('指定した管理機能ディレクトリは既に存在しています。別の名前を指定してください。');
        }

        if (!rename(HTML_REALDIR . $oldAdminDir, HTML_REALDIR . $adminDir)) {
            throw new \Exception(HTML_REALDIR . $adminDir . 'へのリネームに失敗しました。ディレクトリの権限を確認してください。');
        }
    }

    /**
     * @throws \Exception
     */
    public function sendInfo()
    {
        $this->sendInfoExecute($this->getSendInfo());
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getSendInfo()
    {
        $system = new System();
        $baseInfo = new BaseInfo();
        $arrBaseInfo = $baseInfo->get();

        return array(
            'site_url' => rtrim(HTTP_URL, '/') . '/',
            'shop_name' => $arrBaseInfo['shop_name'],
            'cube_ver' => ECCUBE_VERSION,
            'php_ver' => phpversion(),
            'db_ver' => $system->getDBVersion(),
            'os_type' => php_uname() . ' ' . $_SERVER['SERVER_SOFTWARE'],
        );
    }

    /**
     * @param array $arrSendData
     * @return string
     * @throws \Exception
     */
    public function sendInfoExecute($arrSendData)
    {
        // サイト情報を送信
        $req = new \HTTP_Request('http://www.ec-cube.net/mall/use_site.php');
        $req->setMethod(HTTP_REQUEST_METHOD_POST);

        foreach ($arrSendData as $key => $val) {
            $req->addPostData($key, $val);
        }

        if (!\PEAR::isError($req->sendRequest())) {
            $response = $req->getResponseBody();
        } else {
            throw new \Exception('');
        }

        return $response;
    }

    public function setBaseInfo($shopName, $adminMail)
    {
        $sqlval = array(
            'id' => 1,
            'shop_name' => $shopName,
            'email01' => $adminMail,
            'email02' => $adminMail,
            'email03' => $adminMail,
            'email04' => $adminMail,
            'top_tpl' => 'default1',
            'product_tpl' => 'default1',
            'detail_tpl' => 'default1',
            'mypage_tpl' => 'default1',
            'update_date' => 'CURRENT_TIMESTAMP',
            'country_id' => DEFAULT_COUNTRY_ID,
        );

        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $cnt = $objQuery->count('dtb_baseinfo');
        if ($cnt > 0) {
            $objQuery->update('dtb_baseinfo', $sqlval);
        } else {
            $objQuery->insert('dtb_baseinfo', $sqlval);
        }
    }
}
