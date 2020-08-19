<?php

namespace Eccube2\Util;

class Zip
{
    /** @var \LC_Page_Admin_Basis_ZipInstall_Ex $page */
    protected $page;

    public function __construct()
    {
        if (!defined('ECCUBE_INSTALL')) {
            throw new \Exception('EC-CUBEのインストールされていません.');
        }

        require_once CLASS_EX_REALDIR . 'page_extends/admin/basis/LC_Page_Admin_Basis_ZipInstall_Ex.php';
        $this->page = new \LC_Page_Admin_Basis_ZipInstall_Ex();
    }

    /**
     * @return string
     */
    public function getCsvDateTime()
    {
        return $this->page->lfGetCsvDatetime();
    }

    /**
     * @return int
     */
    public function countCsv()
    {
        return $this->page->countZipCsv();
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->page->countZipCsv();
    }

    /**
     * @param callable|null $function
     * @throws \Exception
     */
    public function update($function = null)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();
        $objQuery->begin();
        $this->download();
        $this->delete();
        $count = $this->insert($function);
        $objQuery->commit();

        return $count;
    }

    /**
     *
     */
    public function download()
    {
        $errorReporting = error_reporting();

        error_reporting(E_ERROR | E_WARNING | E_PARSE);
        set_error_handler(
            function ($errno, $errstr) use ($errorReporting) {
                restore_error_handler();
                error_reporting($errorReporting);
                throw new \Exception($errstr);
            }
        );

        $this->page->lfDownloadZipFileFromJp();
        $this->page->lfExtractZipFile();

        restore_error_handler();
        error_reporting($errorReporting);
    }

    public function delete()
    {
        $this->page->lfDeleteZip();
    }

    /**
     * @param int $start
     * @param callable|null $function
     * @throws \Exception
     */
    public function insert($start = 1, $function = null)
    {
        $objQuery = \SC_Query_Ex::getSingletonInstance();

        $cntLine = $this->page->countZipCsv();

        /** 現在行(CSV形式。空行は除く。) */
        $cntCurrentLine = 0;
        /** 挿入した行数 */
        $cntInsert = 0;

        $fp = $this->page->openZipCsv();
        if (!$fp) {
            throw new \Exception(ZIP_CSV_UTF8_REALFILE . ' の読み込みに失敗しました。');
        }
        while (!feof($fp)) {
            $arrCSV = fgetcsv($fp, ZIP_CSV_LINE_MAX);
            if (empty($arrCSV)) continue;
            $cntCurrentLine++;
            if ($cntCurrentLine >= $start) {
                $sqlval = array();
                $sqlval['zip_id'] = $cntCurrentLine;
                $sqlval['zipcode'] = $arrCSV[2];
                $sqlval['state'] = $arrCSV[6];
                $sqlval['city'] = $arrCSV[7];
                $sqlval['town'] = $arrCSV[8];
                $objQuery->insert('mtb_zip', $sqlval);
                $cntInsert++;
            }

            if (is_callable($function)) {
                $function($cntCurrentLine, $cntLine);
            }
        }
        fclose($fp);

        return $cntInsert;
    }
}
