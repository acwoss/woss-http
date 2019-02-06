<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

declare(strict_types=1);

namespace Woss\Http\Server;

use Woss\Http\Message\Request;
use Woss\Http\Message\Response;
use Woss\Http\Message\Stream;

class Resource
{
    /**
     * {@inheritdoc}
     */
    public function handle(Request $request): Response
    {
        $method = strtolower($request->getMethod());

        if (!method_exists($this, $method)) {
            $body = new Stream(
                json_encode([
                    'message' => "O método {$method} não é permitido para o recurso " . get_class($this)
                ])
            );

            $methodsAllowed = get_class_methods($this);

            if (($key = array_search('handle', $methodsAllowed)) !== false) {
                unset($methodsAllowed[$key]);
            }

            $methodsAllowed = join(', ', array_map('strtoupper', $methodsAllowed));

            $response = new Response($body, 405, [
                'Content-Type' => 'application/json',
                'Allow' => $methodsAllowed
            ]);

            return $response;
        }

        return call_user_func([$this, $method], $request);
    }
}