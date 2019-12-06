<?php

use \lightswitch05\PhpVersionAudit\Rules;

class RulesTest extends \Codeception\Test\Unit
{
    private static $RULES_RAW;

    public function _before()
    {
        self::$RULES_RAW = file_get_contents(__DIR__ . '/../../docs/rules-v1.json');
    }

    public function _after()
    {
        file_put_contents(__DIR__ . '/../../docs/rules-v1.json', self::$RULES_RAW);
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
        $this->expectException(\lightswitch05\PhpVersionAudit\Exceptions\StaleRulesException::class);
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

    public function testItLoadsRulesWithUpdate()
    {
        $rules = Rules::loadRules(false);
        $this->assertNotNull($rules);
    }

    public function testItSavesRules()
    {
        Rules::saveRules([], [], []);
    }
}
