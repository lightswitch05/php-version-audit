<?php

use \lightswitch05\PhpVersionAudit\Exceptions\ParseException;

class ParseExceptionTest extends \Codeception\Test\Unit
{
    public function testItCreatesFromString()
    {
        $exception = ParseException::fromString('exception message');
        $this->assertNotNull($exception);
        $this->assertEquals('Parse error: exception message', $exception->getMessage());
    }

    public function testItCreatesFromNull()
    {
        $exception = ParseException::fromString(null);
        $this->assertNotNull($exception);
        $this->assertEquals('Parse error: ', $exception->getMessage());
    }
}
