<?php

namespace Eccube2\Util;

class OwnersStoreUtil
{
    /** @var \LC_Page_Upgrade_Base */
    protected $page;

    /** @var \LC_Page_Upgrade_Download */
    protected $pageDownload;

    /** @var \LC_Page_Admin_OwnersStore_Settings */
    protected $pageSettings;

    /** @var \LC_Upgrade_Helper_Log */
    protected $objLog;

    /** @var array|null */
    protected $lastCopyLog;

    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストールされていません.');
        }

        require_once CLASS_REALDIR . 'pages/upgrade/LC_Page_Upgrade_Base.php';
        $this->page = new \LC_Page_Upgrade_Base();
        require_once CLASS_REALDIR . 'pages/upgrade/LC_Page_Upgrade_Download.php';
        $this->pageDownload = new \LC_Page_Upgrade_Download();
        require_once CLASS_REALDIR . 'pages/admin/ownersstore/LC_Page_Admin_OwnersStore_Settings.php';
        $this->pageSettings = new \LC_Page_Admin_OwnersStore_Settings();
        $this->objLog = new \LC_Upgrade_Helper_Log();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getProductList()
    {
        $mode = 'products_list';

        $this->objLog->start($mode);

        // 認証キーの取得
        $public_key = $this->getPublicKey();
        $sha1_key = $this->createSeed();

        // リクエストを開始
        $arrPostData = array(
            'eccube_url' => HTTP_URL,
            'public_key' => sha1($public_key . $sha1_key),
            'sha1_key'   => $sha1_key,
            'ver'        => ECCUBE_VERSION
        );
        list($objReq, $objData) = $this->request($mode, $arrPostData);

        $this->objLog->log('* get products list ok');
        $arrProducts = array();
        foreach ($objData as $product) {
            $arrProducts[] = get_object_vars($product);
        }

        $this->objLog->end();

        return $arrProducts;
    }

    /**
     * EC-CUBE 本体の最新パッチをダウンロードして適用する.
     *
     * @return object 配信サーバーが返した商品情報 (dl_file は空にしてある)
     * @throws \Exception
     */
    public function patchDownload()
    {
        return $this->downloadAndUpdate('patch_download', array(
            'patch_code' => 'latest',
        ));
    }

    /**
     * 自動アップデートが有効なモジュールをダウンロードして適用する.
     *
     * @param int $productId オーナーズストアの商品ID
     * @return object 配信サーバーが返した商品情報 (dl_file は空にしてある)
     * @throws \Exception
     */
    public function autoDownload($productId)
    {
        $mode = 'auto_update';
        $this->objLog->start($mode);

        $this->objLog->log('* auto update check start');
        if ($this->page->autoUpdateEnable($productId) !== true) {
            $this->objLog->error(OSTORE_E_C_AUTOUP_DISABLE);
            $this->throwException(OSTORE_E_C_AUTOUP_DISABLE);
        }

        return $this->downloadAndUpdate($mode, array(
            'product_id' => $productId,
        ), false);
    }

    /**
     * モジュール・プラグインをダウンロードして適用する.
     *
     * 管理画面のオーナーズストア「ダウンロード」と同じ処理 (LC_Page_Upgrade_Download).
     *
     * @param int $productId オーナーズストアの商品ID
     * @return object 配信サーバーが返した商品情報 (dl_file は空にしてある)
     * @throws \Exception
     */
    public function download($productId)
    {
        return $this->downloadAndUpdate('download', array(
            'product_id' => $productId,
        ));
    }

    /**
     * 直前の download() / autoDownload() / patchDownload() でのファイルコピーの結果.
     *
     * @return array|null SC_Batch_Update::execute() の戻り値 (err / ok / buckup_path)
     */
    public function getLastCopyLog()
    {
        return $this->lastCopyLog;
    }

    /**
     * @param string $mode 配信サーバーへのリクエストの mode
     * @param array $arrParams 認証情報以外に送るパラメーター
     * @param bool $startLog ログの開始を記録するか (呼び出し元で記録済みなら false)
     * @return object
     * @throws \Exception
     */
    protected function downloadAndUpdate($mode, array $arrParams, $startLog = true)
    {
        if ($startLog) {
            $this->objLog->start($mode);
        }

        // 認証キーの取得
        $public_key = $this->getPublicKey();
        $sha1_key = $this->createSeed();

        $arrPostData = array_merge(array(
            'eccube_url' => HTTP_URL,
            'public_key' => sha1($public_key . $sha1_key),
            'sha1_key'   => $sha1_key,
        ), $arrParams);
        list($objReq, $objData) = $this->request($mode, $arrPostData);

        $exract_dir = $this->saveFile(base64_decode($objData->dl_file));
        $objData->dl_file = '';

        $this->update($objData, $exract_dir);

        // 配信サーバーへ通知
        $this->notifyDownload($mode, $objReq->getResponseCookies());

        $this->objLog->end();

        return $objData;
    }

    /**
     * @param object $objData
     * @param $exract_dir
     * @throws \Exception
     */
    public function update($objData, $exract_dir)
    {
        $this->objLog->log('* copy batch start');
        @include_once CLASS_REALDIR . 'batch/SC_Batch_Update.php';
        $objBatch = new \SC_Batch_Update();
        $arrCopyLog = $objBatch->execute($exract_dir);
        $this->lastCopyLog = $arrCopyLog;

        $this->objLog->log('* copy batch check start');
        if (count($arrCopyLog['err']) > 0) {
            $this->pageDownload->registerUpdateLog($arrCopyLog, $objData);
            $this->pageDownload->updateMdlTable($objData);

            $this->objLog->error(OSTORE_E_C_BATCH_ERR, $arrCopyLog);
            $this->throwException(OSTORE_E_C_BATCH_ERR, $arrCopyLog['err']);
        }

        // dtb_module_update_logの更新
        $this->objLog->log('* insert dtb_module_update start');
        $this->pageDownload->registerUpdateLog($arrCopyLog, $objData);

        // dtb_moduleの更新
        $this->objLog->log('* insert/update dtb_module start');
        $this->pageDownload->updateMdlTable($objData);

        // DB更新ファイルの読み込み、実行
        $this->objLog->log('* file execute start');
        $this->pageDownload->fileExecute($objData->product_code);
    }

    /**
     * @param string $data
     * @return string
     * @throws \Exception
     */
    public function saveFile($data)
    {
        // ダウンロードデータの保存
        $this->objLog->log('* save file start');
        $time = time();
        $dir  = DATA_REALDIR . 'downloads/tmp/';
        $filename = $time . '.tar.gz';

        $this->objLog->log("* open {$filename} start");
        if ($fp = @fopen($dir . $filename, 'w')) {
            @fwrite($fp, $data);
            @fclose($fp);
        } else {
            $this->objLog->error(OSTORE_E_C_PERMISSION, $dir . $filename);
            $this->throwException(OSTORE_E_C_PERMISSION);
        }

        // ダウンロードアーカイブを展開する
        $exract_dir = $dir . $time;
        $this->objLog->log("* mkdir {$exract_dir} start");
        if (!@mkdir($exract_dir)) {
            $this->objLog->error(OSTORE_E_C_PERMISSION, $exract_dir);
            $this->throwException(OSTORE_E_C_PERMISSION);
        }

        $this->objLog->log("* extract {$dir}{$filename} start");
        $tar = new \Archive_Tar($dir . $filename);
        $tar->extract($exract_dir);

        return $exract_dir;
    }

    /**
     * @param string $mode
     * @param string $arrPostData
     * @return array
     * @throws \Exception
     */
    public function request($mode, $arrPostData)
    {
        $objJson = new \LC_Upgrade_Helper_Json();

        $this->objLog->log('* http request start');

        $objReq = $this->page->request($mode, $arrPostData);

        // リクエストチェック
        $this->objLog->log('* http request check start');
        if (\PEAR::isError($objReq)) {
            $this->objLog->error(OSTORE_E_C_HTTP_REQ, $objReq);
            $this->throwException(OSTORE_E_C_HTTP_REQ);
        }

        // レスポンスチェック
        $this->objLog->log('* http response check start');
        if ($objReq->getResponseCode() !== 200) {
            $this->objLog->error(OSTORE_E_C_HTTP_RESP, $objReq);
            $this->throwException(OSTORE_E_C_HTTP_RESP);
        }

        $body = $objReq->getResponseBody();
        $objRet = $objJson->decode($body);

        // JSONデータのチェック
        $this->objLog->log('* json deta check start');
        if (empty($objRet)) {
            $this->objLog->error(OSTORE_E_C_FAILED_JSON_PARSE, $objReq);
            $this->throwException(OSTORE_E_C_FAILED_JSON_PARSE);
        }

        // ステータスチェック
        $this->objLog->log('* json status check start');
        if ($objRet->status !== OSTORE_STATUS_SUCCESS) {
            $this->objLog->error($objRet->errcode, $objReq);
            throw new \Exception(preg_replace('|<br\s*/?>|', "\n", $objRet->msg), $objRet->errcode);
        }

        return array($objReq, $objRet->data);
    }

    public function notifyDownload($mode, $arrCookies)
    {
        // 配信サーバーへ通知
        $this->objLog->log('* notify to lockon server start');
        $objReq = $this->pageDownload->notifyDownload($mode, $arrCookies);
        $this->objLog->log('* dl commit result:' . serialize($objReq));
    }

    /**
     * @return string|null
     */
    public function getPublicKey()
    {
        $public_key = $this->page->getPublicKey();

        $this->objLog->log('* public key check start');
        if (empty($public_key)) {
            $this->objLog->error(OSTORE_E_C_NO_KEY);
            $this->throwException(OSTORE_E_C_NO_KEY);
        }

        return $public_key;
    }

    public function setPublicKey($publicKey)
    {
        $this->pageSettings->registerOwnersStoreSettings(array(
            'public_key' => $publicKey,
        ));
    }

    /**
     * @return string
     */
    public function createSeed()
    {
        return $this->page->createSeed();
    }

    /**
     * @param int $errCode
     * @param string[] $arrDetails メッセージに追記する詳細 (コピーに失敗したファイルなど)
     * @throws \Exception
     */
    public function throwException($errCode, array $arrDetails = array())
    {
        $objJson = new \LC_Upgrade_Helper_Json();
        $objJson->setError($errCode);

        $message = preg_replace('|<br\s*/?>|', "\n", $objJson->arrData['msg']);
        if (!empty($arrDetails)) {
            $message .= "\n" . implode("\n", $arrDetails);
        }

        throw new \Exception($message, $objJson->arrData['errcode']);
    }
}
