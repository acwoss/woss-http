<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Woss\Http\Test\Server;

use PHPUnit\Framework\TestCase;
use Woss\Http\Message\Request;
use Woss\Http\Message\Response;
use Woss\Http\Message\Stream;
use Woss\Http\Server\Pipeline;
use Woss\Http\Server\Resource;

class ResouceTest extends TestCase
{
    /**
     * @var Pipeline
     */
    private $pipeline;

    public function setUp(): void
    {
        $this->pipeline = new Pipeline([
            new class extends Resource
            {
                public function get(Request $request): Response
                {
                    $content = json_encode(['ping' => microtime()]);

                    $body = new Stream('php://memory', 'w');
                    $body->write($content);

                    $response = new Response($body, 200, [
                        'Content-Type' => 'application/json',
                        'Content-Length' => strlen($content),
                    ]);

                    return $response;
                }
            }
        ]);
    }

    public function testPipelineWithResource()
    {
        $request = new Request('/');
        $response = $this->pipeline->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertSame(['application/json'], $response->getHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('Content-Length'));

        $data = json_decode((string)$response->getBody(), true);

        $this->assertTrue(key_exists('ping', $data));
    }

    public function testResourceWithWrongMethod()
    {
        $request = new Request('/', 'POST');
        $response = $this->pipeline->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(405, $response->getStatusCode());
        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertSame(['application/json'], $response->getHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('Content-Length'));
        $this->assertTrue($response->hasHeader('Allow'));

        $data = json_decode((string)$response->getBody(), true);

        $this->assertTrue(key_exists('message', $data));
    }
}
