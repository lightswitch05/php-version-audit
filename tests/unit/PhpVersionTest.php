<?php

use \lightswitch05\PhpVersionAudit\PhpVersion;

class PhpVersionTest extends \Codeception\Test\Unit
{

    public function testItParsesFromSimpleString()
    {
        $versionString = "7.3.12";
        $version = PhpVersion::fromString($versionString);
        $this->assertNotNull($version);
        $this->assertEquals((string)$version, $versionString);
        $this->assertEquals($version->getMajor(), 7);
        $this->assertEquals($version->getMinor(), 3);
        $this->assertEquals($version->getPatch(), 12);
        $this->assertEquals($version->getMajorMinorVersionString(), "7.3");
        $this->assertFalse($version->isPreRelease());
        $this->assertEquals(json_encode($versionString), json_encode($versionString));
    }

    public function testItParsesNull()
    {
        $version = PhpVersion::fromString(null);
        $this->assertNull($version);
    }

    public function testItParsesBetaString()
    {
        $version = PhpVersion::fromString("7.4.0beta1");
        $this->assertNotNull($version);
        $this->assertTrue($version->isPreRelease());
    }

    public function testItParsesAlphaString()
    {
        $version = PhpVersion::fromString("7.4.0alpha1");
        $this->assertNotNull($version);
    }

    public function testItParsesRCString()
    {
        $version = PhpVersion::fromString("7.4.0RC1");
        $this->assertNotNull($version);
    }

    public function testItParsesReleaseCandidateString()
    {
        $version = PhpVersion::fromString("7.4.0 release candidate 1");
        $this->assertNotNull($version);
    }

    public function testItComparesMajorVersion()
    {
        $this->compareVersions("7.3.12", "6.4.13");
    }

    public function testItComparesMinorVersion()
    {
        $this->compareVersions("7.4.12", "7.3.12");
    }

    public function testItComparesPatchVersion()
    {
        $this->compareVersions("7.3.13", "7.3.12");
    }

    public function testItComparesEqual()
    {
        $one = PhpVersion::fromString("7.3.13");
        $two = PhpVersion::fromString("7.3.13");
        $this->assertEquals(0, $one->compareTo($two));
        $this->assertEquals(0, $two->compareTo($one));
    }

    public function testItConvertsToJson()
    {
        $version = PhpVersion::fromString("7.3.13");
        $this->assertEquals(json_encode("7.3.13"), json_encode($version));
    }

    public function testItComparesPreRelease()
    {
        $this->compareVersions("7.4.0", "7.4.0beta1");
    }

    public function testItComparesSamePrerelaseType()
    {
        $this->compareVersions("7.4.0beta2", "7.4.0beta1");
    }

    public function testItComparesAlphaToBeta()
    {
        $this->compareVersions("7.4.0beta1", "7.4.0alpha2");
    }

    public function testItComparesAlphaToRc()
    {
        $this->compareVersions("7.4.0rc1", "7.4.0alpha2");
    }

    public function testItComparesBetaToRc()
    {
        $this->compareVersions("7.4.0rc1", "7.4.0beta2");
    }

    private function compareVersions(string $largest, string $smallest): void
    {
        $largestVersion = PhpVersion::fromString($largest);
        $smallestVersion = PhpVersion::fromString($smallest);
        $this->assertLessThan(0, $smallestVersion->compareTo($largestVersion));
        $this->assertGreaterThan(0, $largestVersion->compareTo($smallestVersion));
    }
}
