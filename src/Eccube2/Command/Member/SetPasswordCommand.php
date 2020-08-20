<?php

namespace Eccube2\Command\Member;

use Eccube2\Init;
use Eccube2\Util\Member;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetPasswordCommand extends Command
{
    protected static $defaultName = 'member:set-password';

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
            ->setName(static::$defaultName)
            ->setDescription('メンバーパスワード設定')
            ->addArgument('login_id', InputArgument::REQUIRED, 'ログインID')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'パスワード')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('password')) {
            $password = $input->getOption('password');
        } else {
            $password = $io->askHidden('パスワードを入力してください.', function ($password) {
                if (empty($password)) {
                    throw new \RuntimeException('パスワードは空にできません。');
                }

                return $password;
            });
        }

        $loginId = $input->getArgument('login_id');
        $member = $this->member->findOneByLoginId($loginId);
        $this->member->setPasswordByLoginId($loginId, $password);

        $io->success($member['name'] . ' のパスワードを変更しました。');
    }
}
