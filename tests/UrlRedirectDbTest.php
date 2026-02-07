<?php

use PHPUnit\Framework\TestCase;

class UrlRedirectDbTest extends TestCase
{
    private function makeValidConfig(): array
    {
        return [
            'host'     => 'localhost',
            'login'    => 'user',
            'pass'     => 'pass',
            'database' => 'testdb',
        ];
    }

    public function testConstructorWithValidConfig(): void
    {
        $db = new UrlRedirectDb($this->makeValidConfig());
        $this->assertInstanceOf(UrlRedirectDb::class, $db);
    }

    public function testConstructorWithOptionalPort(): void
    {
        $config = $this->makeValidConfig();
        $config['port'] = 3307;
        $db = new UrlRedirectDb($config);
        $this->assertInstanceOf(UrlRedirectDb::class, $db);
    }

    public function testConstructorRejectsNonArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UrlRedirectDb('not an array');
    }

    public function testConstructorRejectsTooFewKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UrlRedirectDb(['host' => 'localhost', 'login' => 'user', 'pass' => 'pass']);
    }

    public function testConstructorRejectsTooManyKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $config = $this->makeValidConfig();
        $config['port'] = 3306;
        $config['extra'] = 'bad';
        new UrlRedirectDb($config);
    }

    public function testGetRedirectUrlReturnsFalseWhenNoShort(): void
    {
        $db = new UrlRedirectDb($this->makeValidConfig());
        $redirector = new UrlRedirector();
        $this->assertFalse($db->getRedirectUrl($redirector));
    }

    public function testSetRedirectUrlReturnsFalseWhenNoShort(): void
    {
        $db = new UrlRedirectDb($this->makeValidConfig());
        $redirector = new UrlRedirector(null, 'https://example.com');
        $this->assertFalse($db->setRedirectUrl($redirector));
    }

    public function testSetRedirectUrlReturnsFalseWhenNoLong(): void
    {
        $db = new UrlRedirectDb($this->makeValidConfig());
        $redirector = new UrlRedirector('abc');
        $this->assertFalse($db->setRedirectUrl($redirector));
    }

    public function testUpdateRedirectUrlReturnsFalseWhenNoShort(): void
    {
        $db = new UrlRedirectDb($this->makeValidConfig());
        $redirector = new UrlRedirector(null, 'https://example.com');
        $redirector->setUser('alice');
        $this->assertFalse($db->updateRedirectUrl($redirector));
    }

    public function testUpdateRedirectUrlReturnsFalseWhenNoLong(): void
    {
        $db = new UrlRedirectDb($this->makeValidConfig());
        $redirector = new UrlRedirector('abc');
        $redirector->setUser('alice');
        $this->assertFalse($db->updateRedirectUrl($redirector));
    }

    public function testUpdateRedirectUrlReturnsFalseWhenNoUser(): void
    {
        $db = new UrlRedirectDb($this->makeValidConfig());
        $redirector = new UrlRedirector('abc', 'https://example.com');
        $this->assertFalse($db->updateRedirectUrl($redirector));
    }
}
