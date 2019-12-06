<?php

use \lightswitch05\PhpVersionAudit\CveId;

class CveIdTest extends \Codeception\Test\Unit
{
    const VALID_CVE_ID = 'CVE-2016-5094';

    public function testItCreatesASimpleIdFromString()
    {
        $cve = CveId::fromString(self::VALID_CVE_ID);
        $this->assertNotNull($cve);
        $this->assertEquals(self::VALID_CVE_ID, $cve->getId());
        $this->assertEquals(self::VALID_CVE_ID, (string) $cve);
        $this->assertEquals(json_encode(self::VALID_CVE_ID), json_encode($cve));
    }

    public function testItAcceptsNullCveId()
    {
        $cve = CveId::fromString(null);
        $this->assertNull($cve);
    }

    public function testParsesLowerCaseCveId()
    {
        $cve = CveId::fromString(strtolower(self::VALID_CVE_ID));
        $this->assertNotNull($cve);
        $this->assertEquals(self::VALID_CVE_ID, (string) $cve);
    }

    public function testItParsesLongCveIds()
    {
        $longCveId = self::VALID_CVE_ID . '1234';
        $cve = CveId::fromString(strtolower($longCveId));
        $this->assertNotNull($cve);
        $this->assertEquals($longCveId, (string) $cve);
    }

    public function testItComparesCveIds()
    {
        $less = 'CVE-2010-1010';
        $greater = 'CVE-2019-1001';
        $lessCve = CveId::fromString($less);
        $greaterCve = CveId::fromString($greater);
        $this->assertLessThan(0, $lessCve->compareTo($greaterCve));
        $this->assertGreaterThan(0, $greaterCve->compareTo($lessCve));
    }

    public function testItComparesEqualCveIds()
    {
        $one = CveId::fromString(self::VALID_CVE_ID);
        $two = CveId::fromString(self::VALID_CVE_ID);
        $this->assertEquals(0, $one->compareTo($two));
        $this->assertEquals(0, $two->compareTo($one));
    }

    public function testItSortsCveIds()
    {
        $less = CveId::fromString('CVE-2010-1010');
        $greater = CveId::fromString('CVE-2019-1001');
        $sorted = CveId::sort([$greater, $less]);
        $this->assertEquals(2, count($sorted));
        $this->assertEquals($sorted[0], $less);
        $this->assertEquals($sorted[1], $greater);
    }
}
