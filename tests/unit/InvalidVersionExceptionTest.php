<?php

use \lightswitch05\PhpVersionAudit\Exceptions\InvalidVersionException;

class InvalidVersionExceptionTest extends \Codeception\Test\Unit
{
    public function testItCreatesFromString()
    {
        $exception = InvalidVersionException::fromString('bad version');
        $this->assertNotNull($exception);
        $this->assertEquals('PhpVersion [bad version] is not valid', $exception->getMessage());
    }

    public function testItCreatesFromNull()
    {
        $exception = InvalidVersionException::fromString(null);
        $this->assertNotNull($exception);
        $this->assertEquals('PhpVersion [] is not valid', $exception->getMessage());
    }
}
