<?php

use \lightswitch05\PhpVersionAudit\Exceptions\UnknownVersionException;

class UnknownVersionExceptionTest extends \Codeception\Test\Unit
{
    public function testItCreatesFromString()
    {
        $exception = UnknownVersionException::fromString('1.2.3');
        $this->assertNotNull($exception);
        $this->assertEquals('Unknown PHP version (1.2.3), perhaps rules are stale.', $exception->getMessage());
    }

    public function testItCreatesFromNull()
    {
        $exception = UnknownVersionException::fromString(null);
        $this->assertNotNull($exception);
        $this->assertEquals('Unknown PHP version (), perhaps rules are stale.', $exception->getMessage());
    }
}
