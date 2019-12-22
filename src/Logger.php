<?php
declare(strict_types=1);


namespace lightswitch05\PhpVersionAudit;


final class Logger
{
    const SILENT = 0;
    const ERROR = 1;
    const WARNING = 2;
    const INFO = 3;
    const DEBUG = 4;

    /**
     * @var int|null $verbosity
     */
    private static $verbosity;

    public static function setVerbosity(?int $verbosity): void
    {
        self::$verbosity = $verbosity;
    }

    public static function error(): void
    {
        self::log(self::ERROR, 'error', func_get_args());
    }

    public static function warning(): void
    {
        self::log(self::WARNING, 'warning', func_get_args());
    }

    public static function info(): void
    {
        self::log(self::INFO, 'info', func_get_args());
    }

    public static function debug(): void
    {
        self::log(self::DEBUG, 'debug', func_get_args());
    }

    private static function getVerbosity(): int
    {
        if (isset(self::$verbosity)) {
            return self::$verbosity;
        }
        return self::ERROR;
    }

    private static function log(int $levelCode, string $levelName, array $messageParts): void
    {
        if (self::getVerbosity() < $levelCode) {
            return;
        }

        $logEvent = (object) [
            'level' => $levelName,
            'time' => DateHelpers::nowString(),
            'message' => ''
        ];
        foreach ($messageParts as $messagePart) {
            if (is_string($messagePart)) {
                $logEvent->message .= $messagePart;
            } else {
                $logEvent->message .= json_encode($messagePart, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        }
        fwrite(STDERR, json_encode($logEvent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
