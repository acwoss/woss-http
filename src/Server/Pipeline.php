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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Traversable;

class Pipeline implements RequestHandlerInterface
{
    /**
     * @var array|Traversable
     */
    private $handlers;

    /**
     * Cria uma nova pipeline para lidar com uma requisição HTTP.
     *
     * @param $handlers array|Traversable Conjunto de manipuladores HTTP
     * @throws InvalidArgumentException Quando o conjunto de entrada não é iterável ou está vazio
     */
    public function __construct($handlers)
    {
        if (!is_iterable($handlers) || count($handlers) === 0) {
            throw new InvalidArgumentException('$handlers precisa ser um iterável não vazio');
        }

        $this->handlers = $handlers;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = current($this->handlers);

        if (!(
            $handler instanceof RequestHandlerInterface ||
            $handler instanceof MiddlewareInterface ||
            is_callable($handler)
        )) {
            throw new InvalidArgumentException(
                '$handler precisa ser RequestHandlerInterface, MiddlewareInterface ou um objeto chamável'
            );
        }

        next($this->handlers);

        if ($handler instanceof RequestHandlerInterface) {
            return $handler->handle($request);
        }

        if ($handler instanceof MiddlewareInterface) {
            return $handler->process($request, $this);
        }

        return call_user_func($handler, $request, $this);
    }
}