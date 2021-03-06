<?php

/*
 * This file is part of EC-CUBE2 CLI.
 *
 * (C) Tsuyoshi Tsurushima.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube2\Command\Cache;

use Eccube2\Init;
use Eccube2\Util\MasterDataUtil;
use Eccube2\Util\ParameterUtil;
use Eccube2\Util\TemplateUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ClearCommand extends Command
{
    protected static $defaultName = 'cache:clear';

    /** @var MasterDataUtil */
    protected $masterData;

    /** @var ParameterUtil */
    protected $parameter;

    /** @var TemplateUtil */
    protected $template;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->masterData = new MasterDataUtil();
        $this->parameter = new ParameterUtil();
        $this->template = new TemplateUtil();
    }

    protected function configure()
    {
        $this
            ->setDescription('キャッシュクリア')
            ->addOption('no-warmup', null, InputOption::VALUE_NONE, 'パラメータキャッシュを再生成しない')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $noWarmup = $input->getOption('no-warmup');

        $this->masterData->clearAllCache();
        $io->success('マスタキャッシュクリア');

        if ($noWarmup) {
            $this->parameter->clearCache();
            $io->success('パラメーターキャッシュクリア');
        } else {
            $this->parameter->createCache();
            $io->success('パラメーターキャッシュクリア/生成');
        }

        $this->template->clearAllCache();
        $io->success('テンプレートキャッシュクリア');

        $io->success('キャッシュをクリアしました。');
    }
}
