<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


final class DownloadException extends \ErrorException
{
    public static function fromString(?string $message): DownloadException
    {
        return new self("Download error: $message");
    }
}
