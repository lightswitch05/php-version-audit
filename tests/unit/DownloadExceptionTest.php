<?php

use \lightswitch05\PhpVersionAudit\Exceptions\DownloadException;

class DownloadExceptionTest extends \Codeception\Test\Unit
{
    public function testItCreatesFromString()
    {
        $exception = DownloadException::fromString('exception message');
        $this->assertNotNull($exception);
        $this->assertEquals('Download error: exception message', $exception->getMessage());
    }

    public function testItCreatesFromNull()
    {
        $exception = DownloadException::fromString(null);
        $this->assertNotNull($exception);
        $this->assertEquals('Download error: ', $exception->getMessage());
    }
}
