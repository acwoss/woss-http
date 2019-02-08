<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Woss\Http\Test\Message;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Woss\Http\Message\Response;
use Woss\Http\Message\Stream;

class ResponseTest extends TestCase
{
    /**
     * @var Response
     */
    protected $response;

    public function setUp(): void
    {
        $this->response = new Response();
    }

    public function testStatusCodeIs200ByDefault()
    {
        $this->assertSame(200, $this->response->getStatusCode());
    }

    public function testStatusCodeMutatorReturnsCloneWithChanges()
    {
        $response = $this->response->withStatus(400);

        $this->assertNotSame($this->response, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testReasonPhraseDefaultsToStandards()
    {
        $response = $this->response->withStatus(422);
        $this->assertSame('Unprocessable Entity', $response->getReasonPhrase());
    }

    public function testCanSetCustomReasonPhrase()
    {
        $response = $this->response->withStatus(422, 'Foo Bar!');

        $this->assertSame('Foo Bar!', $response->getReasonPhrase());
    }

    public function invalidReasonPhrases()
    {
        return [
            'true' => [true],
            'false' => [false],
            'array' => [[200]],
            'object' => [(object)['reasonPhrase' => 'Ok']],
            'integer' => [99],
            'float' => [400.5],
            'null' => [null],
        ];
    }

    /**
     * @dataProvider invalidReasonPhrases
     * @param $invalidReasonPhrase
     */
    public function testWithStatusReturnsNullForNonStringReasonPhrases($invalidReasonPhrase)
    {
        $response = $this->response->withStatus(422, $invalidReasonPhrase);

        $this->assertNull($response);
    }

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->expectException(InvalidArgumentException::class);
        new Response(['TOTALLY INVALID']);
    }

    public function testConstructorCanAcceptAllMessageParts()
    {
        $body = new Stream('php://memory');
        $status = 302;
        $headers = ['Location' => ['http://example.com/']];

        $response = new Response($body, $status, $headers);

        $this->assertSame($body, $response->getBody());
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($headers, $response->getHeaders());
    }

    public function validStatusCodes()
    {
        return [
            'minimum' => [100],
            'middle' => [300],
            'string-integer' => ['300'],
            'maximum' => [599],
        ];
    }

    /**
     * @dataProvider validStatusCodes
     * @param $code
     */
    public function testCreateWithValidStatusCodes($code)
    {
        $response = $this->response->withStatus($code);

        $result = $response->getStatusCode();

        $this->assertSame((int)$code, $result);
        $this->assertIsInt($result);
    }

    public function invalidStatusCodes()
    {
        return [
            'true' => [true],
            'false' => [false],
            'array' => [[200]],
            'object' => [(object)['statusCode' => 200]],
            'too-low' => [99],
            'float' => [400.5],
            'too-high' => [600],
            'null' => [null],
            'string' => ['foo'],
        ];
    }

    /**
     * @dataProvider invalidStatusCodes
     * @param $code
     */
    public function testCannotSetInvalidStatusCode($code)
    {
        $response = $this->response->withStatus($code);

        $this->assertNull($response);
    }

    public function invalidResponseBody()
    {
        return [
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.1],
            'array' => [['BODY']],
            'stdClass' => [(object)['body' => 'BODY']],
        ];
    }

    /**
     * @dataProvider invalidResponseBody
     * @param $body
     */
    public function testConstructorRaisesExceptionForInvalidBody($body)
    {
        $this->expectException(InvalidArgumentException::class);

        new Response($body);
    }

    public function invalidHeaderTypes()
    {
        return [
            'indexed-array' => [[['INVALID']], 'header name'],
            'null' => [['x-invalid-null' => null]],
            'true' => [['x-invalid-true' => true]],
            'false' => [['x-invalid-false' => false]],
            'object' => [['x-invalid-object' => (object)['INVALID']]],
        ];
    }

    /**
     * @dataProvider invalidHeaderTypes
     * @param $headers
     * @param string $contains
     */
    public function testConstructorRaisesExceptionForInvalidHeaders($headers)
    {
        $this->expectException(InvalidArgumentException::class);

        new Response('php://memory', 200, $headers);
    }

    public function testReasonPhraseCanBeEmpty()
    {
        $response = $this->response->withStatus(555);

        $this->assertIsString($response->getReasonPhrase());
        $this->assertEmpty($response->getReasonPhrase());
    }

    public function headersWithInjectionVectors()
    {
        return [
            'name-with-cr' => ["X-Foo\r-Bar", 'value'],
            'name-with-lf' => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf' => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf' => ["X-Foo\r\n\r\n-Bar", 'value'],
            'value-with-cr' => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf' => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf' => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf' => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr' => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf' => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf' => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf' => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
        ];
    }

    /**
     * @dataProvider headersWithInjectionVectors
     * @param $name
     * @param $value
     */
    public function testConstructorRaisesExceptionForHeadersWithCRLFVectors($name, $value)
    {
        $this->expectException(InvalidArgumentException::class);

        new Response('php://memory', 200, [$name => $value]);
    }

    public function testResponseToString()
    {
        $content = '{"message": "Ok"}';

        $body = new Stream('php://memory', 'w');
        $body->write($content);

        $response = new Response($body, 200, [
            'Content-Type' => 'application/json',
            'Content-Lenght' => 17
        ]);

        $expected = "HTTP/1.1 200 OK\nContent-Type: application/json\nContent-Lenght: 17\n\n{\"message\": \"Ok\"}";

        $this->assertSame($expected, (string)$response);
    }

}