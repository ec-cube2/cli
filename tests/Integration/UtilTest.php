<?php

namespace Eccube2\Tests\Integration;

class UtilTest extends IntegrationTestCase
{
    public function testRandomStringWithLength()
    {
        $result = $this->eccube(['util:random-string', '16']);

        $this->assertRegExpCompat('/^[0-9A-Za-z]{16}$/m', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    public function testRandomStringDefaultLength()
    {
        $result = $this->eccube(['util:random-string']);

        $this->assertRegExpCompat('/^[0-9A-Za-z]{40}$/m', $result->stdout, $result->describe());
        $this->assertCommandSucceeded($result);
    }

    /**
     * assertRegExp (PHPUnit 8) と assertMatchesRegularExpression (PHPUnit 9) の差を吸収する。
     */
    private function assertRegExpCompat(string $pattern, string $string, string $message)
    {
        $this->assertSame(1, preg_match($pattern, $string), $message);
    }
}
