<?php

use \lightswitch05\PhpVersionAudit\CveDetails;
use \lightswitch05\PhpVersionAudit\CveId;

class CveDetailsTest extends \Codeception\Test\Unit
{
    const CVE_ID = 'CVE-2019-1234';
    private static $CVE_ID;

    protected function _before()
    {
        self::$CVE_ID = CveId::fromString(self::CVE_ID);
    }


    public function testBasicConstruction()
    {
        $cve = new CveDetails(self::$CVE_ID, null, null, null, null);
        $this->assertNotNull($cve);
        $this->assertEquals(self::$CVE_ID, $cve->getId());
        $this->assertEquals(json_encode([
            "id" => self::CVE_ID,
            "baseScore" => null,
            "publishedDate" => null,
            "lastModifiedDate" => null,
            "description" => null
        ]), json_encode($cve));
    }

    public function testFullConstruction()
    {
        $cve = new CveDetails(self::$CVE_ID, 5.5, "2016-05-22T01:59:00+0000", "2018-10-30T16:27:00+0000", 'description');
        $this->assertNotNull($cve);
        $this->assertEquals(self::$CVE_ID, $cve->getId());
        $this->assertEquals(json_encode([
            "id" => self::CVE_ID,
            "baseScore" => 5.5,
            "publishedDate" => '2016-05-22T01:59:00+0000',
            "lastModifiedDate" => '2018-10-30T16:27:00+0000',
            "description" => 'description'
        ]), json_encode($cve));
    }
}
