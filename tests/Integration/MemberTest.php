<?php

namespace Eccube2\Tests\Integration;

/**
 * 管理画面メンバーの操作。
 *
 * 各テストは setUp で member:create を実行して専用のメンバーを作る。
 * member:create の終了コードは testCreate で確認し、setUp では DB に作られたことだけを確認する。
 */
class MemberTest extends IntegrationTestCase
{
    /** @var string */
    private $loginId;

    /** @var CommandResult */
    private $createResult;

    protected function setUp(): void
    {
        $this->loginId = 'clitest' . substr(md5(uniqid('', true)), 0, 8);
        $this->createResult = $this->eccube([
            'member:create',
            '--name=CLI テスト ' . $this->loginId,
            '--department=テスト',
            '--login_id=' . $this->loginId,
            '--password=Passw0rd' . $this->loginId,
            '--authority=1',
            '--work=1',
        ]);

        $this->assertNotNull($this->findMember(), 'member:create でメンバーが作られていません' . "\n" . $this->createResult->describe());
    }

    protected function tearDown(): void
    {
        // 削除済みでなければ削除する (終了コードは問わない)
        if ($this->findMember() !== null) {
            $this->eccube(['member:delete', $this->loginId]);
        }
    }

    /**
     * @return array<string, mixed>|null 削除されていないメンバー
     */
    private function findMember()
    {
        $rows = $this->query('SELECT * FROM dtb_member WHERE login_id = ? AND del_flg = 0', [$this->loginId]);

        return $rows ? $rows[0] : null;
    }

    public function testCreate()
    {
        $member = $this->findMember();
        $this->assertSame('1', (string) $member['authority']);
        $this->assertSame('1', (string) $member['work']);
        $this->assertSame('テスト', $member['department']);
        $this->assertCommandSucceeded($this->createResult);
    }

    /**
     * --authority=0 (システム管理者) と --work=0 (非稼働) を指定できること。
     */
    public function testCreateWithZeroValues()
    {
        $loginId = $this->loginId . 'z';
        $result = $this->eccube([
            'member:create',
            '--name=CLI テスト ' . $loginId,
            '--login_id=' . $loginId,
            '--password=Passw0rd' . $loginId,
            '--authority=0',
            '--work=0',
        ]);

        $rows = $this->query('SELECT * FROM dtb_member WHERE login_id = ? AND del_flg = 0', [$loginId]);
        if ($rows) {
            $this->eccube(['member:delete', $loginId]);
        }

        $this->assertNotEmpty($rows, 'メンバーが作られていません' . "\n" . $result->describe());
        $this->assertSame('0', (string) $rows[0]['authority'], '--authority=0 が反映されていません' . "\n" . $result->describe());
        $this->assertSame('0', (string) $rows[0]['work'], '--work=0 が反映されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testDisableAndEnable()
    {
        $disable = $this->eccube(['member:disable', $this->loginId]);
        $this->assertSame('0', (string) $this->findMember()['work'], 'member:disable で非稼働になっていません' . "\n" . $disable->describe());

        $enable = $this->eccube(['member:enable', $this->loginId]);
        $this->assertSame('1', (string) $this->findMember()['work'], 'member:enable で稼働になっていません' . "\n" . $enable->describe());

        $this->assertCommandSucceeded($disable);
        $this->assertCommandSucceeded($enable);
    }

    public function testSetPassword()
    {
        $before = $this->findMember()['password'];
        $result = $this->eccube(['member:set-password', $this->loginId, '--password=Chang3d' . $this->loginId]);

        $this->assertNotSame($before, $this->findMember()['password'], 'パスワードが変更されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testDelete()
    {
        $result = $this->eccube(['member:delete', $this->loginId]);

        $this->assertNull($this->findMember(), 'メンバーが削除されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testDeleteUnknownMemberFails()
    {
        $result = $this->eccube(['member:delete', 'no-such-member-' . $this->loginId]);

        $this->assertStringContainsString('メンバーが見つかりません', $result->output(), $result->describe());
        $this->assertCommandFailed($result);
    }
}
