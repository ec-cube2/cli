<?php

namespace Eccube2\Command\Member;

use Eccube2\Init;
use Eccube2\Util\Member;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteCommand extends Command
{
    protected static $defaultName = 'member:delete';

    /** @var Member */
    protected $member;

    public function initialize(InputInterface $input, OutputInterface $output)
    {
        Init::init();

        $this->member = new Member();
    }

    protected function configure()
    {
        $this
            ->setDescription('メンバー削除')
            ->addArgument('login_id', InputArgument::REQUIRED, 'ログインID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $loginId = $input->getArgument('login_id');
        $member = $this->member->findOneByLoginId($loginId);
        $this->member->deleteByLoginId($loginId);

        $io->success($member['name'] . ' を削除しました。');
    }
}
