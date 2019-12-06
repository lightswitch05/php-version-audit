<?php

use \lightswitch05\PhpVersionAudit\PhpRelease;
use \lightswitch05\PhpVersionAudit\PhpVersion;

class PhpReleaseTest extends \Codeception\Test\Unit
{
    private static $VERSION_STRING = '7.4.0';
    private static $PHP_VERSION;
    private static $PHP_RELEASE_DATE = "2019-11-28T00:00:00+0000";

    protected function _before()
    {
        self::$PHP_VERSION = PhpVersion::fromString(self::$VERSION_STRING);
    }

    public function testItParsesASimpleString()
    {
        $release = PhpRelease::fromReleaseDescription(self::$PHP_VERSION, null, null);
        $this->assertNotEmpty($release);
        $this->assertEmpty($release->getPatchedCveIds());
        $this->assertEquals(self::$PHP_VERSION, $release->getVersion());
        $this->assertEquals(json_encode([
            'releaseDate' => null,
            'patchedCves' => []
        ]), json_encode($release));
    }

    public function testItParsesMultipleCves()
    {
        $release = PhpRelease::fromReleaseDescription(self::$PHP_VERSION, self::$PHP_RELEASE_DATE, "CVE-2019-1234 CVE-2018-1234");
        $this->assertNotEmpty($release);
        $this->assertNotEmpty($release->getPatchedCveIds());
        $this->assertEquals(json_encode([
            'releaseDate' => self::$PHP_RELEASE_DATE,
            'patchedCves' => [
                'CVE-2018-1234',
                'CVE-2019-1234'
            ]
        ]), json_encode($release));
    }

    public function testItComparesVersions()
    {
        $largest = PhpRelease::fromReleaseDescription(PhpVersion::fromString("7.4.0"), null, null);
        $smallest = PhpRelease::fromReleaseDescription(PhpVersion::fromString("7.3.13"), null, null);
        $this->assertLessThan(0, $smallest->compareTo($largest));
        $this->assertGreaterThan(0, $largest->compareTo($smallest));
    }

    public function testItSorts()
    {
        $largest = PhpRelease::fromReleaseDescription(PhpVersion::fromString("7.4.0"), null, null);
        $smallest = PhpRelease::fromReleaseDescription(PhpVersion::fromString("7.3.13"), null, null);
        $sorted = PhpRelease::sort([$largest, $smallest]);
        $this->assertNotEmpty($sorted);
        $this->assertEquals((string) $smallest->getVersion(), (string) $sorted[0]->getVersion());
        $this->assertEquals((string) $largest->getVersion(), (string) $sorted[1]->getVersion());
    }
}
