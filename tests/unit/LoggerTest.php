<?php

use \lightswitch05\PhpVersionAudit\Logger;

class LoggerTest extends \Codeception\Test\Unit
{
    public function testItLogsErrors()
    {
        Logger::setVerbosity(Logger::ERROR);
        Logger::error('error', 1, 2, 3);
    }

    public function testItDefaultsToErrorLevel()
    {
        Logger::setVerbosity(null);
        Logger::error('error', 1, 2, 3);
    }

    public function testItIsSilent()
    {
        Logger::setVerbosity(Logger::SILENT);
        Logger::error('error', 1, 2, 3);
    }

    public function testItLogsInfo()
    {
        Logger::setVerbosity(Logger::INFO);
        Logger::info('info', 1, 2, 3);
    }

    public function testItLogsWarning()
    {
        Logger::setVerbosity(Logger::WARNING);
        Logger::warning('warning', 1, 2, 3);
    }

    public function testItLogsDebug()
    {
        Logger::setVerbosity(Logger::DEBUG);
        Logger::debug('debug', 1, 2, 3);
    }
}
