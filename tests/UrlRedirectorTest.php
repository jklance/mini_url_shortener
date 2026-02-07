<?php

use PHPUnit\Framework\TestCase;

class UrlRedirectorTest extends TestCase
{
    public function testConstructorSetsShortAndLong(): void
    {
        $r = new UrlRedirector('abc123', 'https://example.com');
        $this->assertSame('abc123', $r->getShort());
        $this->assertSame('https://example.com', $r->getLong());
    }

    public function testConstructorWithNoArgs(): void
    {
        $r = new UrlRedirector();
        $this->assertNull($r->getShort());
        $this->assertNull($r->getLong());
    }

    public function testConstructorWithOnlyShort(): void
    {
        $r = new UrlRedirector('abc123');
        $this->assertSame('abc123', $r->getShort());
        $this->assertNull($r->getLong());
    }

    public function testConstructorWithOnlyLong(): void
    {
        $r = new UrlRedirector(null, 'https://example.com');
        $this->assertNull($r->getShort());
        $this->assertSame('https://example.com', $r->getLong());
    }

    public function testSetShortValidCodes(): void
    {
        $r = new UrlRedirector();
        $this->assertSame('test', $r->setShort('test'));
        $this->assertSame('Test_123', $r->setShort('Test_123'));
        $this->assertSame('a', $r->setShort('a'));
    }

    public function testSetShortExactly20Chars(): void
    {
        $r = new UrlRedirector();
        $code = str_repeat('a', 20);
        $this->assertSame($code, $r->setShort($code));
    }

    public function testSetShortRejectsOver20Chars(): void
    {
        $r = new UrlRedirector();
        $this->assertNull($r->setShort(str_repeat('a', 21)));
    }

    public function testSetShortRejectsInvalid(): void
    {
        $r = new UrlRedirector();
        $this->assertNull($r->setShort(''));
        $this->assertNull($r->setShort('has spaces'));
        $this->assertNull($r->setShort('special!chars'));
    }

    public function testSetShortRejectsNull(): void
    {
        $r = new UrlRedirector();
        $this->assertNull($r->setShort(null));
    }

    public function testSetShortTrimsWhitespace(): void
    {
        $r = new UrlRedirector();
        $this->assertSame('abc', $r->setShort('  abc  '));
    }

    public function testSetShortStripsQuerystring(): void
    {
        $r = new UrlRedirector();
        $this->assertSame('abc', $r->setShort('abc?fbclid=junk'));
    }

    public function testSetLongValidUrl(): void
    {
        $r = new UrlRedirector();
        $this->assertSame('https://example.com/path', $r->setLong('https://example.com/path'));
    }

    public function testSetLongRejectsInvalid(): void
    {
        $r = new UrlRedirector();
        $this->assertNull($r->setLong('not-a-url'));
        $this->assertNull($r->setLong(''));
    }

    public function testSetLongRejectsNull(): void
    {
        $r = new UrlRedirector();
        $this->assertNull($r->setLong(null));
    }

    public function testSetAndGetUser(): void
    {
        $r = new UrlRedirector();
        $this->assertSame('alice', $r->setUser('alice'));
        $this->assertSame('alice', $r->getUser());
    }

    public function testSetUserRejectsNull(): void
    {
        $r = new UrlRedirector();
        $this->assertNull($r->setUser(null));
        $this->assertNull($r->getUser());
    }

    public function testSetUserRejectsEmptyString(): void
    {
        $r = new UrlRedirector();
        $this->assertNull($r->setUser(''));
        $this->assertNull($r->getUser());
    }

    public function testGetRedirectHeaderReturnsTrueWhenLongSet(): void
    {
        $r = new UrlRedirector('abc', 'https://example.com');
        // header() calls will fail outside of a real HTTP context,
        // so we catch the warning and just verify the return value
        $result = @$r->getRedirectHeader();
        $this->assertTrue($result);
    }

    public function testGetRedirectHeaderReturnsFalseWhenNoLong(): void
    {
        $r = new UrlRedirector();
        $this->assertFalse($r->getRedirectHeader());
    }
}
