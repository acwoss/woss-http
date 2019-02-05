<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

namespace Woss\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Woss\Http\Message\Request;
use Woss\Http\Message\Stream;
use Woss\Http\Message\Uri;

class RequestTest extends TestCase
{
    /**
     * @var Request
     */
    private $request;

    public function setUp(): void
    {
        $this->request = new Request();
    }

    public function testMethodIsGetByDefault()
    {
        $this->assertSame('GET', $this->request->getMethod());
    }

    public function testMethodMutatorReturnsCloneWithChangedMethod()
    {
        $request = $this->request->withMethod('POST');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals('POST', $request->getMethod());
    }

    public function invalidMethod()
    {
        return [
            [null],
            [''],
        ];
    }

    /**
     * @dataProvider invalidMethod
     */
    public function testWithInvalidMethod($method)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->request->withMethod($method);
    }

    public function testReturnsUnpopulatedUriByDefault()
    {
        $uri = $this->request->getUri();

        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertEmpty($uri->getScheme());
        $this->assertEmpty($uri->getUserInfo());
        $this->assertEmpty($uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertEmpty($uri->getPath());
        $this->assertEmpty($uri->getQuery());
        $this->assertEmpty($uri->getFragment());
    }

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->expectException(InvalidArgumentException::class);

        new Request(['TOTALLY INVALID']);
    }

    public function testWithUriReturnsNewInstanceWithNewUri()
    {
        $request = $this->request->withUri(new Uri('https://example.com:10082/foo/bar?baz=bat'));

        $this->assertNotSame($this->request, $request);

        $request2 = $request->withUri(new Uri('/baz/bat?foo=bar'));

        $this->assertNotSame($this->request, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertSame('/baz/bat?foo=bar', (string) $request2->getUri());
    }

    public function testConstructorCanAcceptAllMessageParts()
    {
        $uri     = new Uri('http://example.com/');
        $body    = new Stream('php://memory');
        $headers = ['X-Foo' => ['bar'],];
        $request = new Request($uri, 'POST', $body, $headers);

        $this->assertSame($uri, $request->getUri());
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame($body, $request->getBody());

        $testHeaders = $request->getHeaders();

        foreach ($headers as $key => $value) {
            $this->assertArrayHasKey($key, $testHeaders);
            $this->assertSame($value, $testHeaders[$key]);
        }
    }

    public function testDefaultStreamIsWritable()
    {
        $request = new Request();

        $request->getBody()->write("test");

        $this->assertSame("test", (string)$request->getBody());
    }

    public function invalidRequestUri()
    {
        return [
            'true'     => [ true ],
            'false'    => [ false ],
            'int'      => [ 1 ],
            'float'    => [ 1.1 ],
            'array'    => [ ['http://example.com'] ],
            'stdClass' => [ (object) [ 'href'         => 'http://example.com'] ],
        ];
    }

    /**
     * @dataProvider invalidRequestUri
     */
    public function testConstructorRaisesExceptionForInvalidUri($uri)
    {
        $this->expectException(InvalidArgumentException::class);
        new Request($uri);
    }

    public function invalidRequestMethod()
    {
        return [
            'bad-string' => [ 'BOGUS METHOD' ],
        ];
    }

    /**
     * @dataProvider invalidRequestMethod
     */
    public function testConstructorRaisesExceptionForInvalidMethod($method)
    {
        $this->expectException(InvalidArgumentException::class);
        new Request(null, $method);
    }

    public function customRequestMethods()
    {
        return[
            /* WebDAV methods */
            'TRACE'     => ['TRACE'],
            'PROPFIND'  => ['PROPFIND'],
            'PROPPATCH' => ['PROPPATCH'],
            'MKCOL'     => ['MKCOL'],
            'COPY'      => ['COPY'],
            'MOVE'      => ['MOVE'],
            'LOCK'      => ['LOCK'],
            'UNLOCK'    => ['UNLOCK'],
            /* Arbitrary methods */
            '#!ALPHA-1234&%' => ['#!ALPHA-1234&%'],
        ];
    }

    /**
     * @dataProvider customRequestMethods
     */
    public function testAllowsCustomRequestMethodsThatFollowSpec($method)
    {
        $request = new Request(null, $method);
        $this->assertSame($method, $request->getMethod());
    }

    public function invalidRequestBody()
    {
        return [
            'true'       => [ true ],
            'false'      => [ false ],
            'int'        => [ 1 ],
            'float'      => [ 1.1 ],
            'array'      => [ ['BODY'] ],
            'stdClass'   => [ (object) [ 'body' => 'BODY'] ],
        ];
    }

    /**
     * @dataProvider invalidRequestBody
     */
    public function testConstructorRaisesExceptionForInvalidBody($body)
    {
        $this->expectException(InvalidArgumentException::class);
        new Request(null, null, $body);
    }

    public function invalidHeaderTypes()
    {
        return [
            'indexed-array' => [[['INVALID']], 'header name'],
            'null' => [['x-invalid-null' => null]],
            'true' => [['x-invalid-true' => true]],
            'false' => [['x-invalid-false' => false]],
            'object' => [['x-invalid-object' => (object) ['INVALID']]],
        ];
    }

    /**
     * @dataProvider invalidHeaderTypes
     */
    public function testConstructorRaisesExceptionForInvalidHeaders($headers, $contains = 'header value type')
    {
        $this->expectException(InvalidArgumentException::class);
        new Request(null, null, 'php://memory', $headers);
    }

    public function testRequestTargetIsSlashWhenNoUriPresent()
    {
        $request = new Request();
        $this->assertSame('/', $request->getRequestTarget());
    }

    public function testRequestTargetIsSlashWhenUriHasNoPathOrQuery()
    {
        $request = (new Request())->withUri(new Uri('http://example.com'));

        $this->assertSame('/', $request->getRequestTarget());
    }

    public function requestsWithUri()
    {
        return [
            'absolute-uri' => [
                (new Request())
                    ->withUri(new Uri('https://api.example.com/user'))
                    ->withMethod('POST'),
                '/user'
            ],
            'absolute-uri-with-query' => [
                (new Request())
                    ->withUri(new Uri('https://api.example.com/user?foo=bar'))
                    ->withMethod('POST'),
                '/user?foo=bar'
            ],
            'relative-uri' => [
                (new Request())
                    ->withUri(new Uri('/user'))
                    ->withMethod('GET'),
                '/user'
            ],
            'relative-uri-with-query' => [
                (new Request())
                    ->withUri(new Uri('/user?foo=bar'))
                    ->withMethod('GET'),
                '/user?foo=bar'
            ],
        ];
    }

    /**
     * @dataProvider requestsWithUri
     */
    public function testReturnsRequestTargetWhenUriIsPresent($request, $expected)
    {
        $this->assertSame($expected, $request->getRequestTarget());
    }

    public function validRequestTargets()
    {
        return [
            'asterisk-form'         => [ '*' ],
            'authority-form'        => [ 'api.example.com' ],
            'absolute-form'         => [ 'https://api.example.com/users' ],
            'absolute-form-query'   => [ 'https://api.example.com/users?foo=bar' ],
            'origin-form-path-only' => [ '/users' ],
            'origin-form'           => [ '/users?id=foo' ],
        ];
    }

    /**
     * @dataProvider validRequestTargets
     */
    public function testCanProvideARequestTarget($requestTarget)
    {
        $request = (new Request())->withRequestTarget($requestTarget);
        $this->assertSame($requestTarget, $request->getRequestTarget());
    }

    public function testRequestTargetCannotContainWhitespace()
    {
        $request = new Request();
        $this->expectException(InvalidArgumentException::class);
        $request->withRequestTarget('foo bar baz');
    }

    public function testRequestTargetDoesNotCacheBetweenInstances()
    {
        $request = (new Request())->withUri(new Uri('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri(new Uri('http://mwop.net/bar/baz'));
        $this->assertNotSame($original, $newRequest->getRequestTarget());
    }

    public function testSettingNewUriResetsRequestTarget()
    {
        $request = (new Request())->withUri(new Uri('https://example.com/foo/bar'));
        $newRequest = $request->withUri(new Uri('http://mwop.net/bar/baz'));
        $this->assertNotSame($request->getRequestTarget(), $newRequest->getRequestTarget());
    }

    public function testGetHeadersContainsHostHeaderIfUriWithHostIsPresent()
    {
        $request = new Request('http://example.com');
        $headers = $request->getHeaders();
        $this->assertArrayHasKey('Host', $headers);
        $this->assertContains('example.com', $headers['Host']);
    }

    public function testGetHeadersContainsNoHostHeaderIfNoUriPresent()
    {
        $request = new Request();
        $headers = $request->getHeaders();
        $this->assertArrayNotHasKey('Host', $headers);
    }

    public function testGetHeadersContainsNoHostHeaderIfUriDoesNotContainHost()
    {
        $request = new Request(new Uri());
        $headers = $request->getHeaders();
        $this->assertArrayNotHasKey('Host', $headers);
    }

    public function testGetHostHeaderReturnsUriHostWhenPresent()
    {
        $request = new Request('http://example.com');
        $header = $request->getHeader('host');
        $this->assertSame(['example.com'], $header);
    }

    public function testGetHostHeaderReturnsEmptyArrayIfNoUriPresent()
    {
        $request = new Request();
        $this->assertSame([], $request->getHeader('host'));
    }

    public function testGetHostHeaderReturnsEmptyArrayIfUriDoesNotContainHost()
    {
        $request = new Request(new Uri());
        $this->assertSame([], $request->getHeader('host'));
    }

    public function testGetHostHeaderLineReturnsUriHostWhenPresent()
    {
        $request = new Request('http://example.com');
        $header = $request->getHeaderLine('host');
        $this->assertStringContainsString('example.com', $header);
    }

    public function testGetHostHeaderLineReturnsEmptyStringIfNoUriPresent()
    {
        $request = new Request();
        $this->assertEmpty($request->getHeaderLine('host'));
    }

    public function testGetHostHeaderLineReturnsEmptyStringIfUriDoesNotContainHost()
    {
        $request = new Request(new Uri());
        $this->assertEmpty($request->getHeaderLine('host'));
    }

    public function testHostHeaderSetFromUriOnCreationIfNoHostHeaderSpecified()
    {
        $request = new Request('http://www.example.com');
        $this->assertTrue($request->hasHeader('Host'));
        $this->assertSame('www.example.com', $request->getHeaderLine('host'));
    }

    public function testHostHeaderNotSetFromUriOnCreationIfHostHeaderSpecified()
    {
        $request = new Request('http://www.example.com', null, 'php://memory', ['Host' => 'www.test.com']);
        $this->assertSame('www.test.com', $request->getHeaderLine('host'));
    }

    public function testPassingPreserveHostFlagWhenUpdatingUriDoesNotUpdateHostHeader()
    {
        $request = (new Request())
            ->withAddedHeader('Host', 'example.com');
        $uri = (new Uri())->withHost('www.example.com');
        $new = $request->withUri($uri, true);
        $this->assertSame('example.com', $new->getHeaderLine('Host'));
    }

    public function testNotPassingPreserveHostFlagWhenUpdatingUriWithoutHostDoesNotUpdateHostHeader()
    {
        $request = (new Request())
            ->withAddedHeader('Host', 'example.com');
        $uri = new Uri();
        $new = $request->withUri($uri);
        $this->assertSame('example.com', $new->getHeaderLine('Host'));
    }

    public function testHostHeaderUpdatesToUriHostAndPortWhenPreserveHostDisabledAndNonStandardPort()
    {
        $request = (new Request())
            ->withAddedHeader('Host', 'example.com');
        $uri = (new Uri())
            ->withHost('www.example.com')
            ->withPort(10081);
        $new = $request->withUri($uri);
        $this->assertSame('www.example.com:10081', $new->getHeaderLine('Host'));
    }

    public function headersWithInjectionVectors()
    {
        return [
            'name-with-cr'           => ["X-Foo\r-Bar", 'value'],
            'name-with-lf'           => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf'         => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf'        => ["X-Foo\r\n\r\n-Bar", 'value'],
            'value-with-cr'          => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf'          => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf'        => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf'       => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr'    => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf'    => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf'  => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf' => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
        ];
    }

    /**
     * @dataProvider headersWithInjectionVectors
     */
    public function testConstructorRaisesExceptionForHeadersWithCRLFVectors($name, $value)
    {
        $this->expectException(InvalidArgumentException::class);
        new Request(null, null, 'php://memory', [$name => $value]);
    }
}
