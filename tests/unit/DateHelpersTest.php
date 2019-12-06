<?php

use \lightswitch05\PhpVersionAudit\DateHelpers;

class DateHelpersTest extends \Codeception\Test\Unit
{
    public function testItParsersFromISO8601ToNull()
    {
        $date = DateHelpers::fromISO8601(null);
        $this->assertNull($date);
    }

    public function testItParsesFromISO8601ToDate()
    {
        $date = DateHelpers::fromISO8601("2019-11-30T03:07:32+0000");
        $this->assertInstanceOf('DateTimeImmutable', $date);
    }

    public function testItParsesFromRFC7231ToNull()
    {
        $date = DateHelpers::fromRFC7231(null);
        $this->assertNull($date);
    }

    public function testItParsesFromRFC7231toDate()
    {
        $date = DateHelpers::fromRFC7231("Sat, 30 Nov 2019 03:18:39 GMT");
        $this->assertInstanceOf('DateTimeImmutable', $date);
    }

    public function testItParsesFromJMYToISO8601WithNull()
    {
        $date = DateHelpers::fromJMYToISO8601(null);
        $this->assertNull($date);
    }

    public function testItParsesFromJMYToISO8601WithDate()
    {
        $date = DateHelpers::fromJMYToISO8601("27 Aug 1986");
        $this->assertEquals('1986-08-27T00:00:00+0000', $date);
    }

    public function testItParsesFromYMDToISO8601WithNull()
    {
        $date = DateHelpers::fromYMDToISO8601(null);
        $this->assertNull($date);
    }

    public function testItParsesFromYMDToISO8601WithString()
    {
        $date = DateHelpers::fromYMDToISO8601('2019-12-25');
        $this->assertEquals('2019-12-25T00:00:00+0000', $date);
    }

    public function testItParsesFromCveFormatToISO8601WithNull()
    {
        $date = DateHelpers::fromCveFormatToISO8601(null);
        $this->assertNull($date);
    }

    public function testItParsesFromCveFormatToISO8601WithDate()
    {
        $date = DateHelpers::fromCveFormatToISO8601("2019-02-26T14:04Z");
        $this->assertEquals('2019-02-26T14:04:00+0000', $date);
    }

    public function testItGetsNowString()
    {
        $now = DateHelpers::nowString();
        $this->assertStringMatchesFormat("%i-%i-%iT%i:%i:%i+0000", $now);
    }

    public function testItGetsNowTimestamp()
    {
        $expectedNow = (new \DateTime())->getTimestamp();
        $now = DateHelpers::nowTimestamp();
        $elapsed = abs($expectedNow - $now);
        $this->assertLessThan(1, $elapsed);
    }

    public function testItFormatsToISO8601FromNull()
    {
        $date = DateHelpers::toISO8601(null);
        $this->assertNull($date);
    }

    public function testItFormatsToISO8601FromDate()
    {
        $date = DateHelpers::toISO8601(new DateTimeImmutable());
        $this->assertTrue(is_string($date));
    }
}
