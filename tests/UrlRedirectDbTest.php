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

    private function makeStubMysqli(): \mysqli
    {
        return $this->createStub(\mysqli::class);
    }

    private function makeDbWithStub(?\mysqli $stub = null): UrlRedirectDb
    {
        return new UrlRedirectDb($stub ?? $this->makeStubMysqli());
    }

    private function makeRedirector(
        ?string $short = null,
        ?string $long = null,
        ?string $user = null
    ): UrlRedirector {
        $r = new UrlRedirector($short, $long);
        if ($user !== null) {
            $r->setUser($user);
        }
        return $r;
    }

    // ── Constructor tests (array path) ──────────────────────────────────

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
        $this->expectException(TypeError::class);
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

    // ── Constructor tests (mysqli path) ─────────────────────────────────

    public function testConstructorAcceptsMysqliInstance(): void
    {
        $db = $this->makeDbWithStub();
        $this->assertInstanceOf(UrlRedirectDb::class, $db);
    }

    // ── Guard tests (no short / no long / no user) ──────────────────────

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

    // ── getRedirectUrl (mock-based) ─────────────────────────────────────

    public function testGetRedirectUrlQueriesDbAndReturnsUrl(): void
    {
        $mysqli = $this->makeStubMysqli();

        $resultStub = $this->createStub(\mysqli_result::class);
        $resultStub->method('fetch_assoc')
            ->willReturn(['redirect_url' => 'https://example.com/long']);

        $stmt = $this->createStub(\mysqli_stmt::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('get_result')->willReturn($resultStub);

        $mysqli->method('prepare')->willReturn($stmt);
        // log INSERT should succeed
        $mysqli->method('query')->willReturn(true);

        $db = new UrlRedirectDb($mysqli);
        $redirector = $this->makeRedirector('abc', 'https://example.com');

        $result = $db->getRedirectUrl($redirector);
        $this->assertSame('https://example.com/long', $result);
    }

    public function testGetRedirectUrlReturnsNullWhenPrepareFails(): void
    {
        $mysqli = $this->makeStubMysqli();
        $mysqli->method('prepare')->willReturn(false);
        $mysqli->method('query')->willReturn(true);

        $db = new UrlRedirectDb($mysqli);
        $redirector = $this->makeRedirector('abc', 'https://example.com');

        $this->assertNull($db->getRedirectUrl($redirector));
    }

    // ── getAllLogEntries (mock-based) ────────────────────────────────────

    public function testGetAllLogEntriesReturnsResultsWithLimit(): void
    {
        $mysqli = $this->makeStubMysqli();

        $resultStub = $this->createStub(\mysqli_result::class);
        $rows = [['short' => 'abc', 'date' => '2025-01-01', 'url' => 'https://example.com', 'user' => 'alice']];
        $resultStub->method('fetch_all')->willReturn($rows);

        $mysqli->method('query')->willReturn($resultStub);

        $db = new UrlRedirectDb($mysqli);
        $result = $db->getAllLogEntries(10);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('abc', $result[0]['short']);
    }

    public function testGetAllLogEntriesReturnsNullOnQueryFailure(): void
    {
        $mysqli = $this->makeStubMysqli();
        $mysqli->method('query')->willReturn(false);

        $db = new UrlRedirectDb($mysqli);
        $this->assertNull($db->getAllLogEntries(10));
    }

    public function testGetAllLogEntriesOmitsLimitWhenNoCount(): void
    {
        $mysqli = $this->createMock(\mysqli::class);

        $resultStub = $this->createStub(\mysqli_result::class);
        $resultStub->method('fetch_all')->willReturn([]);

        $mysqli->expects($this->once())
            ->method('query')
            ->with($this->callback(function (string $sql) {
                return strpos($sql, 'LIMIT') === false;
            }))
            ->willReturn($resultStub);

        $db = new UrlRedirectDb($mysqli);
        $db->getAllLogEntries();
    }

    // ── getTopShorts (mock-based) ───────────────────────────────────────

    public function testGetTopShortsReturnsResultsWithLimit(): void
    {
        $mysqli = $this->makeStubMysqli();

        $resultStub = $this->createStub(\mysqli_result::class);
        $rows = [['short' => 'abc', 'count' => '5', 'url' => 'https://example.com', 'user' => 'alice']];
        $resultStub->method('fetch_all')->willReturn($rows);

        $mysqli->method('query')->willReturn($resultStub);

        $db = new UrlRedirectDb($mysqli);
        $result = $db->getTopShorts(5);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('abc', $result[0]['short']);
    }

    // ── getAllShorts (mock-based) ────────────────────────────────────────

    public function testGetAllShortsReturnsResults(): void
    {
        $mysqli = $this->makeStubMysqli();

        $resultStub = $this->createStub(\mysqli_result::class);
        $rows = [['short' => 'xyz', 'user' => 'bob', 'url' => 'https://example.com', 'created' => '2025-01-01']];
        $resultStub->method('fetch_all')->willReturn($rows);

        $mysqli->method('query')->willReturn($resultStub);

        $db = new UrlRedirectDb($mysqli);
        $result = $db->getAllShorts();

        $this->assertIsArray($result);
        $this->assertSame('xyz', $result[0]['short']);
    }

    // ── setRedirectUrl (mock-based) ─────────────────────────────────────

    public function testSetRedirectUrlBuildsInsertAndReturnsTrue(): void
    {
        $mysqli = $this->createMock(\mysqli::class);
        $mysqli->expects($this->once())
            ->method('query')
            ->with($this->stringContains('INSERT INTO redirects'))
            ->willReturn(true);

        $db = new UrlRedirectDb($mysqli);
        $redirector = $this->makeRedirector('abc', 'https://example.com', 'alice');

        $this->assertTrue($db->setRedirectUrl($redirector));
    }

    public function testSetRedirectUrlReturnsFalseOnQueryFailure(): void
    {
        $mysqli = $this->makeStubMysqli();
        $mysqli->method('query')->willReturn(false);

        $db = new UrlRedirectDb($mysqli);
        $redirector = $this->makeRedirector('abc', 'https://example.com', 'alice');

        $this->assertFalse($db->setRedirectUrl($redirector));
    }

    // ── updateRedirectUrl (mock-based) ──────────────────────────────────

    public function testUpdateRedirectUrlBuildsUpdateAndReturnsTrue(): void
    {
        $mysqli = $this->createMock(\mysqli::class);
        $mysqli->expects($this->once())
            ->method('query')
            ->with($this->stringContains('UPDATE redirects'))
            ->willReturn(true);

        $db = new UrlRedirectDb($mysqli);
        $redirector = $this->makeRedirector('abc', 'https://example.com', 'alice');

        $this->assertTrue($db->updateRedirectUrl($redirector));
    }

    public function testUpdateRedirectUrlReturnsFalseOnQueryFailure(): void
    {
        $mysqli = $this->makeStubMysqli();
        $mysqli->method('query')->willReturn(false);

        $db = new UrlRedirectDb($mysqli);
        $redirector = $this->makeRedirector('abc', 'https://example.com', 'alice');

        $this->assertFalse($db->updateRedirectUrl($redirector));
    }

    // ── Connection ownership ────────────────────────────────────────────

    public function testInjectedConnectionIsNeverClosed(): void
    {
        $mysqli = $this->createMock(\mysqli::class);
        $mysqli->expects($this->never())->method('close');

        $resultStub = $this->createStub(\mysqli_result::class);
        $resultStub->method('fetch_all')->willReturn([]);

        $mysqli->method('query')->willReturnCallback(function (string $sql) use ($resultStub) {
            if (str_starts_with($sql, 'SELECT')) {
                return $resultStub;
            }
            return true;
        });

        $stmtResultStub = $this->createStub(\mysqli_result::class);
        $stmtResultStub->method('fetch_assoc')
            ->willReturn(['redirect_url' => 'https://example.com']);

        $stmt = $this->createStub(\mysqli_stmt::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('get_result')->willReturn($stmtResultStub);
        $mysqli->method('prepare')->willReturn($stmt);

        $db = new UrlRedirectDb($mysqli);

        // Exercise several methods that all call _openHandle / _closeHandle
        $db->getRedirectUrl($this->makeRedirector('abc', 'https://example.com'));
        $db->setRedirectUrl($this->makeRedirector('abc', 'https://example.com', 'alice'));
        $db->updateRedirectUrl($this->makeRedirector('abc', 'https://example.com', 'alice'));
        $db->getAllLogEntries();
        $db->getTopShorts();
        $db->getAllShorts();
    }
}
