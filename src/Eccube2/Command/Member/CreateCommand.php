<?php

namespace Eccube2\Command\Member;

use Eccube2\Init;
use Eccube2\Util\Member;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateCommand extends Command
{
    protected static $defaultName = 'member:create';

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
            ->setDescription('メンバー作成')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, '名前')
            ->addOption('department', null, InputOption::VALUE_OPTIONAL, '所属')
            ->addOption('login_id', null, InputOption::VALUE_REQUIRED, 'ログインID')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'パスワード')
            ->addOption('authority', null, InputOption::VALUE_REQUIRED, '権限')
            ->addOption('work', null, InputOption::VALUE_REQUIRED, '稼働/非稼働')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        // name
        if ($input->getOption('name')) {
            $member['name'] = $input->getOption('name');
        } else {
            $_member = $this->member;
            $member['name'] = $io->ask('名前を入力してください。', null, function ($name) use ($_member) {
                if (empty($name)) {
                    throw new \RuntimeException('名前は空にできません。');
                }

                if ($_member->exests('name = ? AND del_flg = 0', $name)) {
                    throw new \RuntimeException('既に登録されている名前なので利用できません。');
                }

                return $name;
            });
        }

        // department
        if ($input->getOption('department')) {
            $member['department'] = $input->getOption('department');
        } else {
            $member['department'] = $io->ask('所属を入力してください.', '', function ($department) {
                return $department;
            });
        }

        // name
        if ($input->getOption('login_id')) {
            $member['login_id'] = $input->getOption('login_id');
        } else {
            $_member = $this->member;
            $member['login_id'] = $io->ask('ログインIDを入力してください。', null, function ($login_id) use ($_member) {
                if (empty($login_id)) {
                    throw new \RuntimeException('ログインIDは空にできません。');
                }

                if ($_member->exests('login_id = ? AND del_flg = 0', $login_id)) {
                    throw new \RuntimeException('既に登録されているIDなので利用できません。');
                }

                return $login_id;
            });
        }

        // password
        if ($input->getOption('password')) {
            $member['password'] = $input->getOption('password');
        } else {
            $member['password'] = $io->askHidden('パスワードを入力してください.', function ($password) {
                if (empty($password)) {
                    throw new \RuntimeException('パスワードは空にできません。');
                }

                return $password;
            });
        }

        // authority
        if ($input->getOption('authority')) {
            $member['authority'] = $input->getOption('authority');
        } else {
            $member['authority'] = $io->choice('権限を選択してください。', $this->member->arrAUTHORITY);
        }

        // work
        if ($input->getOption('work')) {
            $member['work'] = $input->getOption('work');
        } else {
            $member['work'] = $io->choice('稼働/非稼働を選択してください。', $this->member->arrWORK);
        }

        $this->member->create($member);

        $io->success($member['name'] . ' を作成しました。');
    }
}
