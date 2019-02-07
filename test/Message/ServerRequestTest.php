<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Woss\Http\Test\Message;

use PHPUnit\Framework\TestCase;
use Woss\Http\Message\ServerRequest;

class ServerRequestTest extends TestCase
{
    /**
     * @var ServerRequest
     */
    protected $request;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
    }

    public function testServerParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getServerParams());
    }

    public function testQueryParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getQueryParams());
    }

    public function testQueryParamsMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];

        $request = $this->request->withQueryParams($value);

        $this->assertNotSame($this->request, $request);
        $this->assertSame($value, $request->getQueryParams());
    }

    public function testCookiesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getCookieParams());
    }

    public function testCookiesMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];

        $request = $this->request->withCookieParams($value);

        $this->assertNotSame($this->request, $request);
        $this->assertSame($value, $request->getCookieParams());
    }

    public function testUploadedFilesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getUploadedFiles());
    }

    public function testParsedBodyIsEmptyByDefault()
    {
        $this->assertEmpty($this->request->getParsedBody());
    }

    public function testParsedBodyMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];

        $request = $this->request->withParsedBody($value);

        $this->assertNotSame($this->request, $request);
        $this->assertSame($value, $request->getParsedBody());
    }

    public function testAttributesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getAttributes());
    }

    public function testSingleAttributesWhenEmptyByDefault()
    {
        $this->assertEmpty($this->request->getAttribute('does-not-exist'));
    }

    /**
     * @depends testAttributesAreEmptyByDefault
     */
    public function testAttributeMutatorReturnsCloneWithChanges()
    {
        $request = $this->request->withAttribute('foo', 'bar');

        $this->assertNotSame($this->request, $request);
        $this->assertSame('bar', $request->getAttribute('foo'));

        return $request;
    }

    /**
     * @depends testAttributeMutatorReturnsCloneWithChanges
     * @param ServerRequest $request Requisição a ser testada.
     */
    public function testRemovingAttributeReturnsCloneWithoutAttribute($request)
    {
        $new = $request->withoutAttribute('foo');

        $this->assertNotSame($request, $new);
        $this->assertNull($new->getAttribute('foo', null));
    }

    public function testCookieParamsAreAnEmptyArrayAtInitialization()
    {
        $request = new ServerRequest();

        $this->assertIsArray($request->getCookieParams());
        $this->assertCount(0, $request->getCookieParams());
    }

    public function testQueryParamsAreAnEmptyArrayAtInitialization()
    {
        $request = new ServerRequest();

        $this->assertIsArray($request->getQueryParams());
        $this->assertCount(0, $request->getQueryParams());
    }

    public function testParsedBodyIsNullAtInitialization()
    {
        $request = new ServerRequest();

        $this->assertNull($request->getParsedBody());
    }

    public function testAllowsRemovingAttributeWithNullValue()
    {
        $request = new ServerRequest();

        $request = $request->withAttribute('boo', null);
        $request = $request->withoutAttribute('boo');

        $this->assertSame([], $request->getAttributes());
    }

    public function testAllowsRemovingNonExistentAttribute()
    {
        $request = new ServerRequest();

        $request = $request->withoutAttribute('boo');

        $this->assertSame([], $request->getAttributes());
    }

    public function testTryToAddInvalidUploadedFiles()
    {
        $request = new ServerRequest();

        $new = $request->withUploadedFiles([null]);

        $this->assertNull($new);
    }
}