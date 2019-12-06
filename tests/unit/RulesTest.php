<?php

use \lightswitch05\PhpVersionAudit\Rules;
use \lightswitch05\PhpVersionAudit\PhpRelease;
use \lightswitch05\PhpVersionAudit\PhpVersion;
use \lightswitch05\PhpVersionAudit\Exceptions\StaleRulesException;

class RulesTest extends \Codeception\Test\Unit
{
    private static $rulesPath = '/../../docs/rules-v1.json';
    private static $rulesRaw;

    public function _before()
    {
        self::$rulesRaw = file_get_contents(__DIR__ . self::$rulesPath);
    }

    public function _after()
    {
        file_put_contents(__DIR__ . self::$rulesPath, self::$rulesRaw);
    }

    public function testItAssertsFreshRules()
    {
        $rules = (object) [
            'lastUpdatedDate' => (new DateTime())->modify('-2 week')->modify('+1 hour')
        ];
        Rules::assertFreshRules($rules);
    }

    public function testItAssertsStaleRules()
    {
        $this->expectException(StaleRulesException::class);
        $rules = (object) [
            'lastUpdatedDate' => (new DateTime())->modify('-2 week')->modify('-1 hour')
        ];
        Rules::assertFreshRules($rules);
    }

    public function testItLoadsRulesWithoutUpdate()
    {
        $rules = Rules::loadRules(true);
        $this->assertNotNull($rules);
    }

    public function testItThrowsOnMissingRules()
    {
        $this->expectException(StaleRulesException::class);
        unlink(__DIR__ . self::$rulesPath);
        Rules::loadRules(true);
    }

    public function testItLoadsRulesWithUpdate()
    {
        $rules = Rules::loadRules(false);
        $this->assertNotNull($rules);
    }

    public function testItEnsuresValidRules()
    {
        $this->expectException(StaleRulesException::class);
        $rules = json_decode(self::$rulesRaw);
        $rules->supportEndDates = [];
        file_put_contents(__DIR__ . self::$rulesPath, json_encode($rules));
        Rules::loadRules(true);
    }

    public function testItSavesRules()
    {
        $releaseOne = PhpRelease::fromReleaseDescription(PhpVersion::fromString('5.4.0'), '2019-11-28T00:00:00+0000', 'CVE-2019-11043 CVE-2019-11041 CVE-2019-11042');
        $releaseTwo = PhpRelease::fromReleaseDescription(PhpVersion::fromString('7.3.0'), '2019-11-28T00:00:00+0000', '');
        $releaseThree = PhpRelease::fromReleaseDescription(PhpVersion::fromString('7.4.0rc'), '2019-11-28T00:00:00+0000', 'CVE-2019-11041');
        $releaseFour = PhpRelease::fromReleaseDescription(PhpVersion::fromString('7.3.1'), '2019-11-28T00:00:00+0000', '');
        $releaseFive = PhpRelease::fromReleaseDescription(PhpVersion::fromString('7.4.0'), '2019-11-28T00:00:00+0000', '');
        Rules::saveRules([$releaseOne, $releaseTwo, $releaseThree, $releaseFour, $releaseFive], [], []);
    }
}
