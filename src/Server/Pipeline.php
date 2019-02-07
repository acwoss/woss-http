<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

declare(strict_types=1);

namespace Woss\Http\Server;

use InvalidArgumentException;
use Traversable;
use Woss\Http\Message\Request;
use Woss\Http\Message\Response;

class Pipeline
{
    /**
     * @var array|Traversable
     */
    private $handlers;

    /**
     * Cria uma nova pipeline para lidar com uma requisição HTTP.
     *
     * @param $handlers array|Traversable Conjunto de manipuladores HTTP
     * @throws InvalidArgumentException Quando $handlers não é iterável ou está vazio
     */
    public function __construct($handlers)
    {
        if (!$this->setHandlers($handlers)) {
            throw new InvalidArgumentException('$handlers precisa ser um iterável não vazio');
        }
    }

    /**
     * Define a lista de manipuladores HTTP.
     *
     * @param array|Traversable $handlers Conjunto de manipuladores HTTP
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setHandlers($handlers): bool
    {
        if (!is_iterable($handlers) || count($handlers) === 0) {
            return false;
        }

        $this->handlers = $handlers;

        return true;
    }

    /**
     * Processa a requisição a partir dos manipuladores HTTP gerando uma resposta.
     *
     * @param Request $request Requisição de entrada.
     * @return Response|null Resposta gerada pelos manipuladores HTTP.
     */
    public function handle(Request $request): ?Response
    {
        $handler = current($this->handlers);

        if (!(
            method_exists($handler, 'handle')
            || method_exists($handler, 'process')
            || is_callable($handler)
        )) {
            return null;
        }

        next($this->handlers);

        if (method_exists($handler, 'handle')) {
            return $handler->handle($request);
        }

        if (method_exists($handler, 'process')) {
            return $handler->process($request, $this);
        }

        return call_user_func($handler, $request, $this);
    }
}