<?php

declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;

final class ParseException extends \ErrorException
{
    public static function fromString(?string $message): ParseException
    {
        return new self("Parse error: $message");
    }

    public static function fromException(\Exception $ex, string $fileName, int $line): ParseException
    {
        return new self($ex->getMessage(), $ex->getCode(), 1, $fileName, $line, $ex);
    }
}
