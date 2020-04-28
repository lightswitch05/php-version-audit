<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


final class DownloadException extends \ErrorException
{
    /**
     * @param string|null $message
     * @return DownloadException
     */
    public static function fromString(?string $message): DownloadException
    {
        return new self("Download error: $message");
    }
}
