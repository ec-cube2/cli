<?php

namespace Eccube2\Tests\Integration;

class InfoTest extends IntegrationTestCase
{
    public function testInfo()
    {
        $version = $this->query("SELECT name FROM mtb_constants WHERE id = 'ECCUBE_VERSION'");
        $result = $this->eccube(['info']);

        $this->assertStringContainsString('EC-CUBEバージョン', $result->stdout, $result->describe());
        if ($version) {
            $this->assertStringContainsString(trim($version[0]['name'], '"'), $result->stdout, $result->describe());
        }
        $this->assertCommandSucceeded($result);
    }
}
