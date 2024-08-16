<?php

declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit;

final class Logger
{
    public const SILENT = 0;
    public const ERROR = 1;
    public const WARNING = 2;
    public const INFO = 3;
    public const DEBUG = 4;

    private static ?int $verbosity = null;

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
        return self::$verbosity ?? self::ERROR;
    }

    private static function log(int $levelCode, string $levelName, array $messageParts): void
    {
        if (self::getVerbosity() < $levelCode) {
            return;
        }

        $logEvent = (object) [
            'level' => $levelName,
            'time' => DateHelpers::nowString(),
            'message' => '',
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
