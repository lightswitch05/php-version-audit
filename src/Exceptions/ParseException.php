<?php
declare(strict_types=1);

namespace lightswitch05\PhpVersionAudit\Exceptions;


final class ParseException extends \ErrorException
{
    private function __construct(
        $message = "",
        $code = 0,
        $severity = 1,
        $filename = __FILE__,
        $lineno = __LINE__,
        $previous = null
    ) {
        parent::__construct($message, $code, $severity, $filename, $lineno, $previous);
    }

    /**
     * @param string|null message
     * @return ParseException
     */
    public static function fromString(?string $message): ParseException
    {
        return new self('Parse error: ' . $message);
    }
}
