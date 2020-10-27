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

class SetKeyCommand extends Command
{
    protected static $defaultName = 'ownersstore:set-key';

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
            ->setDescription('オーナーズストア認証キー設定')
            ->addArgument('public_key', InputArgument::REQUIRED, '認証キー')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $publicKey = $input->getArgument('public_key');
        $this->ownersStore->setPublicKey($publicKey);

        $io->success('認証キーを設定しました。');
    }
}
