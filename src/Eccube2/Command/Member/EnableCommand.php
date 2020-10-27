<?php

namespace Eccube2\Command\Member;

use Eccube2\Init;
use Eccube2\Util\MemberUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EnableCommand extends Command
{
    protected static $defaultName = 'member:enable';

    /** @var MemberUtil */
    protected $member;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->member = new MemberUtil();
    }

    protected function configure()
    {
        $this
            ->setDescription('メンバー有効化')
            ->addArgument('login_id', InputArgument::REQUIRED, 'ログインID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $loginId = $input->getArgument('login_id');
        $member = $this->member->findOneByLoginId($loginId);
        $this->member->setWorkByLoginId($loginId, 1);

        $io->success($member['name'] . ' を有効化しました。');
    }
}
