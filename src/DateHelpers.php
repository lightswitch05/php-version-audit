<?php
declare(strict_types=1);


namespace lightswitch05\PhpVersionAudit;


final class DateHelpers
{
    public static function fromISO8601(?string $date): ?\DateTimeImmutable
    {
        return self::fromFormat(\DateTime::ISO8601, $date);
    }

    public static function fromRFC7231(?string $date): ?\DateTimeImmutable
    {
        return self::fromFormat(\DateTime::RFC7231, $date);
    }

    public static function fromTimestamp(int $date): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp($date);
    }

    public static function fromJMYToISO8601(?string $date): ?string
    {
        $dateTime = self::fromFormat('j M Y', $date);
        if ($dateTime !== null) {
            $dateTime = $dateTime->setTime(0, 0, 0);
        }
        return self::toISO8601($dateTime);
    }

    public static function fromYMDToISO8601(?string $date): ?string
    {
        $dateTime = self::fromFormat('Y-m-d', $date);
        if ($dateTime !== null) {
            $dateTime = $dateTime->setTime(0, 0, 0);
        }
        return self::toISO8601($dateTime);
    }

    public static function fromCveFormatToISO8601(?string $date): ?string
    {
        $dateTime = self::fromFormat('Y-m-d\TH:i\Z', $date);
        return self::toISO8601($dateTime);
    }

    public static function nowString(): string
    {
        return self::toISO8601(new \DateTimeImmutable());
    }

    public static function nowTimestamp(): int
    {
        return (new \DateTimeImmutable())->getTimestamp();
    }

    public static function toISO8601(?\DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }
        return $date->format(\DateTime::ISO8601);
    }

    private static function fromFormat(string $format, ?string $date): ?\DateTimeImmutable
    {
        if ($date && $newDate = \DateTimeImmutable::createFromFormat($format, $date)) {
            return $newDate;
        }
        return null;
    }
}
