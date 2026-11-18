<?php

namespace Eccube2\Tests\Integration;

class ParameterTest extends IntegrationTestCase
{
    const KEY = 'SEARCH_PMAX';

    /** @var string|null */
    private $original;

    protected function setUp(): void
    {
        $rows = $this->query('SELECT name FROM mtb_constants WHERE id = ?', [self::KEY]);
        $this->assertNotEmpty($rows, self::KEY . ' が mtb_constants にありません');
        $this->original = $rows[0]['name'];
    }

    protected function tearDown(): void
    {
        if ($this->original !== null) {
            $this->eccube(['parameter:set', self::KEY, $this->original]);
        }
    }

    public function testGet()
    {
        $result = $this->eccube(['parameter:get', self::KEY]);

        $this->assertStringContainsString($this->original, $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testSet()
    {
        $result = $this->eccube(['parameter:set', self::KEY, '25']);

        $rows = $this->query('SELECT name FROM mtb_constants WHERE id = ?', [self::KEY]);
        $this->assertSame('25', $rows[0]['name'], 'mtb_constants が更新されていません' . "\n" . $result->describe());
        $this->assertCommandSucceeded($result);
    }
}
