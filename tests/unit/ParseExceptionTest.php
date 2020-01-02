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

    public function testItCreatesFromException()
    {
        $ex = new Exception('Test Exception');
        $parseException = ParseException::fromException($ex, 'filename.php', 500);
        $this->assertNotNull($parseException);
        $this->assertEquals('Test Exception', $parseException->getMessage());
        $this->assertEquals(500, $parseException->getLine());
        $this->assertEquals('filename.php', $parseException->getFile());
        $this->assertEquals($ex, $parseException->getPrevious());
    }
}
