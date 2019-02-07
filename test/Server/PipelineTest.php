<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Woss\Http\Test\Server;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Woss\Http\Message\Request;
use Woss\Http\Message\Response;
use Woss\Http\Message\Stream;
use Woss\Http\Server\Pipeline;

class PipelineTest extends TestCase
{
    public function invalidPipelines()
    {
        return [
            [null],
            [''],
            [1],
            [[]],
            [(object)['name' => 'middleware']],
            [function () {}],
        ];
    }

    /**
     * @dataProvider invalidPipelines
     * @param $pipeline
     */
    public function testConstructorWithInvalidParameter($pipeline)
    {
        $this->expectException(InvalidArgumentException::class);

        new Pipeline($pipeline);
    }

    public function testIfHandlerReturnsAnResponse()
    {
        $pipeline = new Pipeline([
            function (Request $request, $next): Response {
                $body = new Stream('php://memory', 'w');

                $body->write('Ok');

                return new Response($body);
            }
        ]);

        $request = new Request('/');

        $response = $pipeline->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('Ok', (string)$response->getBody());
    }

    public function testPipelineWithBypassHandler()
    {
        $pipeline = new Pipeline([
            function (Request $request, $next): Response {
                return $next->handle($request);
            },

            function (Request $request, $next): Response {
                $body = new Stream('php://memory', 'w');

                $body->write('Ok');

                return new Response($body);
            }
        ]);

        $request = new Request('/');

        $response = $pipeline->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('Ok', (string)$response->getBody());
    }

    public function testPipelineWithTwoHandlers()
    {
        $pipeline = new Pipeline([
            function (Request $request, $next): Response {
                /**
                 * @var Response $response
                 */
                $response = $next->handle($request);

                return $response->withAddedHeader('X-Handler-A', 'Ok');
            },

            function (Request $request, $next): Response {
                $response = new Response();

                return $response->withAddedHeader('X-Handler-B', 'Ok');
            }
        ]);

        $request = new Request('/');

        $response = $pipeline->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertTrue($response->hasHeader('X-Handler-A'));
        $this->assertTrue($response->hasHeader('X-Handler-B'));
        $this->assertSame(['Ok'], $response->getHeader('X-Handler-A'));
        $this->assertSame(['Ok'], $response->getHeader('X-Handler-B'));
    }

    public function testPipelineWithAddedHeaders()
    {
        $pipeline = new Pipeline([
            function (Request $request, $next): Response {
                /**
                 * @var Response $response
                 */
                $response = $next->handle($request);

                return $response->withAddedHeader('X-Handlers', 'A');
            },

            function (Request $request, $next): Response {
                $response = new Response();

                return $response->withAddedHeader('X-Handlers', 'B');
            }
        ]);

        $request = new Request('/');

        $response = $pipeline->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertTrue($response->hasHeader('X-Handlers'));
        $this->assertSame(['B', 'A'], $response->getHeader('X-Handlers'));
    }

    public function testPipelineWithOverwritedHeader()
    {
        $pipeline = new Pipeline([
            function (Request $request, $next): Response {
                /**
                 * @var Response $response
                 */
                $response = $next->handle($request);

                return $response->withHeader('X-Handlers', 'A');
            },

            function (Request $request, $next): Response {
                $response = new Response();

                return $response->withHeader('X-Handlers', 'B');
            }
        ]);

        $request = new Request('/');

        $response = $pipeline->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertTrue($response->hasHeader('X-Handlers'));
        $this->assertSame(['A'], $response->getHeader('X-Handlers'));
    }

    public function testPipelineWithoutHeader()
    {
        $pipeline = new Pipeline([
            function (Request $request, $next): Response {
                /**
                 * @var Response $response
                 */
                $response = $next->handle($request);

                return $response->withoutHeader('X-Handlers');
            },

            function (Request $request, $next): Response {
                $response = new Response();

                return $response->withHeader('X-Handlers', 'B');
            }
        ]);

        $request = new Request('/');

        $response = $pipeline->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertFalse($response->hasHeader('X-Handlers'));
    }
}
