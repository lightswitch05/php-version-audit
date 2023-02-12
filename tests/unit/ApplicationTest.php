<?php

use \lightswitch05\PhpVersionAudit\Application;
use \lightswitch05\PhpVersionAudit\Exceptions\InvalidVersionException;
use \lightswitch05\PhpVersionAudit\Exceptions\UnknownVersionException;

class ApplicationTest extends \Codeception\Test\Unit
{
    public function testItHasVulnerabilities()
    {
        $app = new Application('4.4.6', true);
        $this->assertTrue($app->hasVulnerabilities());
    }

    public function testItDoesNotHaveVulnerabilities()
    {
        $latestVersion = (new Application('7.4.1', true))->getLatestVersion();
        $app = new Application($latestVersion, true);
        $this->assertFalse($app->hasVulnerabilities());
    }

    public function testItRequiresAValidVersion()
    {
        $this->expectException(InvalidVersionException::class);
        new Application('7.4', true);
    }

    public function testItGetsLatestVersion()
    {
        $latestVersion = (new Application('7.4.0', true))->getLatestVersion();
        $result = (new Application($latestVersion, true))->isLatestVersion();
        $this->assertTrue($result);
    }

    public function testItIsNotLatestVersion()
    {
        $result = (new Application('7.3.0', true))->isLatestVersion();
        $this->assertFalse($result);
    }

    public function testItGetsLatestPatchVersion()
    {
        $latestVersion = (new Application('7.3.0', true))->getLatestPatchVersion();
        $result = (new Application($latestVersion, true))->isLatestPatchVersion();
        $this->assertTrue($result);
    }

    public function testItIsNotLatestPatchVersion()
    {
        $result = (new Application('7.3.11', true))->isLatestVersion();
        $this->assertFalse($result);
    }

    public function testItGetsLatestMinorVersion()
    {
        $latestVersion = (new Application('7.4.0', true))->getLatestMinorVersion();
        $result = (new Application($latestVersion, true))->isLatestMinorVersion();
        $this->assertTrue($result);
    }

    public function testItIsNotLatestMinorVersion()
    {
        $result = (new Application('7.3.12', true))->isLatestMinorVersion();
        $this->assertFalse($result);
    }

    public function testUnknownMajorLatestMinorVersion()
    {
        $this->expectException(UnknownVersionException::class);
        (new Application('6.0.0', true))->getLatestMinorVersion();
    }

    public function testUnknownMajorLatestPatchVersion()
    {
        $this->expectException(UnknownVersionException::class);
        (new Application('1.0.0', true))->getLatestpatchVersion();
    }

    public function testUnknownMinorLatestPatchVersion()
    {
        $this->expectException(UnknownVersionException::class);
        (new Application('7.50.0', true))->getLatestPatchVersion();
    }

    public function testUnknownPatchLatestPatchVersion()
    {
        $this->expectException(UnknownVersionException::class);
        (new Application('7.2.200', true))->getLatestPatchVersion();
    }

    public function testGetSecurityEndDateValid()
    {
        $endDate = (new Application('7.4.0', true))->getSecuritySupportEndDate();
        $this->assertNotEmpty($endDate);
    }

    public function testGetSecurityEndDateInvalid()
    {
        $this->expectException(UnknownVersionException::class);
        (new Application('6.1.0', true))->getSecuritySupportEndDate();
    }

    public function testGetSecurityEndDateOld()
    {
        $endDate = (new Application('7.1.0', true))->getSecuritySupportEndDate();
        $this->assertNotEmpty($endDate);
    }

    public function testGetSecurityEndDatePreRelease()
    {
        $endDate = (new Application('7.4.0rc4', true))->getSecuritySupportEndDate();
        $this->assertNull($endDate);
    }

    public function testItHasSecuritySupportValid()
    {
        $hasSupport = (new Application('7.4.0', true))->hasSecuritySupport();
        $this->assertTrue(is_bool($hasSupport));
    }

    public function testItHasSecuritySupportOld()
    {
        $hasSupport = (new Application('7.0.0', true))->hasSecuritySupport();
        $this->assertTrue(is_bool($hasSupport));
    }

    public function testItHasSecuritySupportUnknown()
    {
        $this->expectException(UnknownVersionException::class);
        (new Application('6.2.0', true))->hasSecuritySupport();
    }

    public function testItHasSecuritySupportPreRelease()
    {
        $hasSupport = (new Application('7.4.0rc3', true))->hasSecuritySupport();
        $this->assertFalse($hasSupport);
    }

    public function testItHasActiveSupportValid()
    {
        $hasSupport = (new Application('7.4.0', true))->hasActiveSupport();
        $this->assertTrue(is_bool($hasSupport));
    }

    public function testItHasActiveSupportOld()
    {
        $hasSupport = (new Application('7.0.0', true))->hasActiveSupport();
        $this->assertTrue(is_bool($hasSupport));
    }

    public function testItHasActiveSupportUnknown()
    {
        $this->expectException(UnknownVersionException::class);
        (new Application('6.3.0', true))->hasActiveSupport();
    }

    public function testItHasActiveSupportPreRelease()
    {
        $hasSupport = (new Application('7.4.0rc1', true))->hasActiveSupport();
        $this->assertTrue(is_bool($hasSupport));
    }

    public function testGetAllAuditDetailsValid()
    {
        $result = (new Application('7.4.0', true))->getAllAuditDetails();
        $this->assertAllAuditDetails($result);
    }

    public function testGetAllAuditDetailsOld()
    {
        $result = (new Application('5.0.0', true))->getAllAuditDetails();
        $this->assertAllAuditDetails($result);
    }

    public function testGetAllAuditDetailsPreRelease()
    {
        $result = (new Application('7.4.0rc1', true))->getAllAuditDetails();
        $this->assertAllAuditDetails($result);
    }

    public function testGetAllAuditDetailsUnknown()
    {
        $this->expectException(UnknownVersionException::class);
        (new Application('6.4.0', true))->getAllAuditDetails();
    }

    public function testGetRulesUpdateDate()
    {
        $date = (new Application('7.4.1', true))->getRulesLastUpdatedDate();
        $this->assertNotEmpty($date);
        $this->assertIsString($date);
    }

    private function assertAllAuditDetails($result)
    {
        $this->assertNotEmpty($result);
        $this->assertTrue(is_string($result->auditVersion));
        $this->assertNotEmpty($result->auditVersion);
        $this->assertTrue(is_bool($result->hasVulnerabilities));
        $this->assertTrue(is_bool($result->hasVulnerabilities));
        $this->assertTrue(is_bool($result->isLatestPatchVersion));
        $this->assertTrue(is_bool($result->isLatestMinorVersion));
        $this->assertTrue(is_bool($result->isLatestVersion));
        $this->assertTrue(is_string($result->latestPatchVersion));
        $this->assertNotEmpty($result->latestPatchVersion);
        $this->assertTrue(is_string($result->latestMinorVersion));
        $this->assertNotEmpty($result->latestMinorVersion);
        $this->assertTrue(is_string($result->latestVersion));
        $this->assertNotEmpty($result->latestVersion);
        $this->assertObjectHasAttribute('activeSupportEndDate', $result);
        $this->assertNotNull($result->vulnerabilities);
    }
}
