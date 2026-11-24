<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command\OwnersStore;

use Eccube2\Init;
use Eccube2\Util\OwnersStoreUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * オーナーズストアからモジュール・プラグインをダウンロードしてインストール / アップデートする.
 *
 * 管理画面の「オーナーズストア > 購入商品一覧」の「ダウンロード」と同じ処理を行う.
 * ファイルは CLI を実行したユーザーの権限で書き込むため,
 * Web サーバーのユーザーにソースコードへの書き込み権限を与えていない環境でも使える.
 */
class DownloadCommand extends Command
{
    protected static $defaultName = 'ownersstore:download';

    /** @var OwnersStoreUtil */
    protected $ownersStore;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->ownersStore = new OwnersStoreUtil();
    }

    protected function configure()
    {
        $this
            ->setDescription('オーナーズストアからダウンロード')
            ->addArgument('product_id', InputArgument::REQUIRED, '商品ID (ownersstore:list で確認できます)')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, '確認せずに実行する')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $productId = $input->getArgument('product_id');
        if (!preg_match('/\A[1-9][0-9]*\z/', $productId)) {
            $io->error('商品ID は数字で指定してください: ' . $productId);

            return 1;
        }

        try {
            $arrProduct = $this->findProduct($productId);
        } catch (\Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }
        if ($arrProduct === null) {
            $io->error('購入商品一覧に商品ID ' . $productId . ' がありません。');

            return 1;
        }

        $io->title('オーナーズストアからダウンロード');
        $io->listing(array(
            '商品: ' . $arrProduct['name'],
            '商品ID: ' . $arrProduct['product_id'],
            'バージョン: ' . $arrProduct['version'],
            '導入バージョン: ' . $arrProduct['installed_version'],
            '購入ステータス: ' . str_replace("\n", '', $arrProduct['status']),
        ));

        $question = (string) $arrProduct['installed_version'] === ''
            ? 'インストールしますか？'
            : $arrProduct['installed_version'] . ' から ' . $arrProduct['version'] . ' にアップデートしますか？';
        if (!$input->getOption('yes') && !$io->confirm($question, false)) {
            $io->text('中止しました。');

            return 1;
        }

        try {
            $objData = $this->ownersStore->download($productId);
        } catch (\Exception $e) {
            $this->writeCopyLog($io);
            $io->error($e->getMessage());

            return 1;
        }

        $this->writeCopyLog($io);
        $io->success($objData->product_name . ' (' . $objData->product_code . ') のインストール/アップデートに成功しました。');

        return 0;
    }

    /**
     * @param string $productId
     * @return array|null
     * @throws \Exception
     */
    protected function findProduct($productId)
    {
        foreach ($this->ownersStore->getProductList() as $arrProduct) {
            if ((string) $arrProduct['product_id'] === (string) $productId) {
                return $arrProduct;
            }
        }

        return null;
    }

    protected function writeCopyLog(SymfonyStyle $io)
    {
        $arrCopyLog = $this->ownersStore->getLastCopyLog();
        if ($arrCopyLog === null) {
            return;
        }

        $io->section('コピーの結果');
        $io->listing(array(
            'コピーしたファイル: ' . count($arrCopyLog['ok']) . ' 件',
            'エラー: ' . count($arrCopyLog['err']) . ' 件',
            'バックアップ: ' . $arrCopyLog['buckup_path'],
        ));
        if ($io->isVerbose()) {
            foreach ($arrCopyLog['ok'] as $line) {
                $io->writeln($line);
            }
        }
    }
}
