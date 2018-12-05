<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

declare(strict_types=1);

namespace Woss\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{
    /**
     * @var string Alvo da requisição HTTP.
     */
    private $requestTarget;

    /**
     * @var UriInterface Instância de URI
     */
    private $uri;

    /**
     * @var string Método da requisição HTTP
     */
    private $method;

    /**
     * Retorna o alvo da requisição HTTP.
     *
     * Se nenhuma URI estiver disponível e nenhum alvo de requisição foi definido, será retornado a string "/".
     *
     * @return string Alvo da requisição HTTP
     */
    public function getRequestTarget()
    {
        if (!is_null($this->requestTarget)) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();

        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if (!$target) {
            $target = "/";
        }

        return $target;
    }

    /**
     * Retorna uma instância com o alvo de requisição informado.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3
     * @param mixed $requestTarget
     * @return RequestInterface
     */
    public function withRequestTarget($requestTarget): RequestInterface
    {
        // TODO: fazer a validação do alvo de requisição
        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    /**
     * Retorna o método HTTP da requisição.
     *
     * @return string Método da requisição HTTP
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Retorna uma instância com o método informado.
     *
     * Como os métodos HTTP são geralmente representados em letras maiúsculas e o nome do método é sensível à
     * maiúsculas e minúsculas, o método não modificará o nome do método informado.
     *
     * @param string $method Nome do método HTTP a ser utilizado
     * @return RequestInterface
     */
    public function withMethod($method): RequestInterface
    {
        // TODO: fazer a validação do método HTTP
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * Retorna a instância de URI.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Instância de URI que representa a requisição HTTP
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Retorna uma instância com a URI informada.
     *
     * Quando o parâmetro `$preserveHost` é definido como `true`:
     *
     * - Se o cabeçalho Host não estiver definido ou estiver vazio e a instância URI possuir o componente Host
     *   o cabeçalho Host será atualizado para o valor em URI.
     * - Se o cabeçalho Host não estiver definido ou estiver vazio e a instância URI não possuir o componente Host
     *   o cabeçalho Host não será atualizado.
     * - Se o cabeçalho estiver definido e não for vazio, seu valor não será alterado.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri Instância de URI a ser utilizada
     * @param bool $preserveHost Preserva o estado do cabeçalho Host
     * @return RequestInterface Requisição HTTP com a nova instância URI
     */
    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        $new = clone $this;
        $new->uri = $uri;

        if ($preserveHost && $new->hasHeader('Host')) {
            return $new;
        }

        if (!$uri->getHost()) {
            return $new;
        }

        $host = $uri->getHost();

        if ($uri->getPort()) {
            $host .= ":" . $uri->getPort();
        }

        $new = $new->withHeader('Host', $host);

        return $new;
    }
}