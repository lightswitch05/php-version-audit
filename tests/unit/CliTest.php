<?php

use \lightswitch05\PhpVersionAudit\Cli;

class CliTest extends \Codeception\Test\Unit
{
    private static $tmpPath = '/../../tmp/';
    private static $tempBackupPath = '/../../tmp-backup/';

    public function _before()
    {
        $this->deleteDir(__DIR__ . self::$tempBackupPath);
        $this->renameDir(__DIR__ . self::$tmpPath, __DIR__ . self::$tempBackupPath);
    }

    public function _after()
    {
        $this->deleteDir(__DIR__ . self::$tmpPath);
        $this->renameDir(__DIR__ . self::$tempBackupPath, __DIR__ . self::$tmpPath);
    }

    public function testRunsNoCache()
    {
        $exitCode = Cli::run();
        $this->assertEquals(0, $exitCode);
    }

    public function testRunsWithCache()
    {
        // first run builds cache
        $exitCode = Cli::run();
        $this->assertEquals(0, $exitCode);

        // second run uses cache
        $exitCode = Cli::run();
        $this->assertEquals(0, $exitCode);
    }

    private function deleteDir($fullPath)
    {
        if (!is_dir($fullPath)) {
            return;
        }
        $files = glob($fullPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($fullPath);
    }

    public function renameDir($oldFullPath, $newFullPath)
    {
        if (!is_dir($oldFullPath)) {
            return;
        }
        if (!is_dir($newFullPath)){
            mkdir($newFullPath);
        }
        $files = glob($oldFullPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            rename($oldFullPath . basename($file), $newFullPath . basename($file));
        }
        $this->deleteDir($oldFullPath);
    }
}
