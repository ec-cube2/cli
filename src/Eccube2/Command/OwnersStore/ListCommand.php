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
use Eccube2\Util\OwnersStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ListCommand extends Command
{
    protected static $defaultName = 'ownersstore:list';

    /** @var OwnersStore */
    protected $ownersStore;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->ownersStore = new OwnersStore();
    }

    protected function configure()
    {
        $this
            ->setName(static::$defaultName)
            ->setDescription('オーナーズストア一覧')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $arrProducts = $this->ownersStore->getProductList();

        $io->title('オーナーズストア一覧');

        $last = count($arrProducts);
        foreach ($arrProducts as $i => $arrProduct) {
            $io->section($arrProduct['name']);
            $io->writeln($arrProduct['main_list_comment']);
            $io->newLine();
            $io->writeln('https://www.ec-cube.net/products/detail.php?product_id=' . $arrProduct['product_id']);
            $io->newLine();

            $io->listing(array(
                '商品ID: ' . $arrProduct['product_id'],
                'バージョン: ' . $arrProduct['version'],
                '導入バージョン: ' . $arrProduct['installed_version'],
                '購入ステータス: ' . str_replace("\n", '', $arrProduct['status']),
                '最終更新: ' . $arrProduct['last_update_date'],
            ));

            if ($i + 1 < $last) {
                $io->newLine();
            }
        }
    }
}
