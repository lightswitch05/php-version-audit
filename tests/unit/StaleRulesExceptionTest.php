<?php

use \lightswitch05\PhpVersionAudit\Exceptions\StaleRulesException;

class StaleRulesExceptionTest extends \Codeception\Test\Unit
{
    public function testItCreatesFromString()
    {
        $exception = StaleRulesException::fromString('stale rules');
        $this->assertNotNull($exception);
        $this->assertEquals('Rules are stale: stale rules', $exception->getMessage());
    }

    public function testItCreatesFromNull()
    {
        $exception = StaleRulesException::fromString(null);
        $this->assertNotNull($exception);
        $this->assertEquals('Rules are stale: ', $exception->getMessage());
    }
}
