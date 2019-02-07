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
     * Lista de métodos permitidos para o recurso.
     */
    const HTTP_METHODS = ['get', 'head', 'post', 'put', 'delete', 'connect', 'options', 'trace'];

    /**
     * Processa uma requisição chamando o método da classe conforme o método HTTP.
     *
     * @param Request $request Requisição de entrada.
     * @return Response Responsa gerada a partir do processamento da requisição.
     */
    public function handle(Request $request): Response
    {
        $method = strtolower($request->getMethod());

        if (!method_exists($this, $method)) {
            $content = json_encode([
                'message' => "O método {$method} não é permitido para o recurso " . get_class($this)
            ]);

            $body = new Stream('php://memory', 'w');
            $body->write($content);

            $methodsAllowed = array_intersect(get_class_methods($this), self::HTTP_METHODS);
            $methodsAllowed = join(', ', array_map('strtoupper', $methodsAllowed));

            $response = new Response($body, 405, [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($content),
                'Allow' => $methodsAllowed
            ]);

            return $response;
        }

        return call_user_func([$this, $method], $request);
    }
}