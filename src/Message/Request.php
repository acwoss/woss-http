<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

declare(strict_types=1);

namespace Woss\Http\Message;

class Request extends Message
{
    /**
     * @var string
     */
    private $requestTarget;

    /**
     * @var Uri
     */
    private $uri;

    /**
     * @var string
     */
    private $method;

    /**
     * Inicializa uma nova instância de Request.
     *
     * @param string|Uri $uri URI da requisição.
     * @param string $method Método da requisição.
     * @param string|resource|Stream $body Corpo da requisição.
     * @param array $headers Headers Lista de cabeçalhos da requisição.
     */
    public function __construct($uri = '', $method = 'GET', $body = 'php://temp', $headers = [])
    {
        parent::__construct($body, $headers);

        $this->setMethod($method);
        $this->setUri($uri);

        if (!$this->hasHeader('Host') && $this->uri->getHost() !== '') {
            $this->setHeader('Host', $this->getHostFromUri());
        }
    }

    /**
     * Retorna o alvo da requisição.
     *
     * @return string Alvo da requisição.
     */
    public function getRequestTarget(): string
    {
        if (!is_null($this->requestTarget)) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();

        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        if ($target === '') {
            $target = "/";
        }

        return $target;
    }

    /**
     * Retorna uma cópia da requisição definindo o novo alvo.
     *
     * @param string $requestTarget Novo alvo da requisição.
     * @return Request|null Cópia da requisição com o novo alvo, nulo em caso de falha.
     */
    public function withRequestTarget($requestTarget): ?Request
    {
        $new = clone $this;

        if (!$new->setRequestTarget($requestTarget)) {
            return null;
        }

        return $new;
    }

    /**
     * Define o novo alvo da requisição.
     *
     * @param string $requestTarget Novo alvo da requisição.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setRequestTarget($requestTarget): bool
    {
        if (preg_match('#\s#', $requestTarget)) {
            return false;
        }

        $this->requestTarget = $requestTarget;

        return true;
    }

    /**
     * Retorna o método da requisição.
     *
     * @return string Método da requisição.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Retorna uma cópia da requisição deinindo o novo método.
     *
     * @param string $method Novo método da requisição.
     * @return Request|null Cópia da requisição com o novo método.
     */
    public function withMethod($method): ?Request
    {
        $new = clone $this;

        if (!$new->setMethod($method)) {
            return null;
        }

        return $new;
    }

    /**
     * Define o novo método da requisição.
     *
     * @param string $method Novo método da requisição.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setMethod($method): bool
    {
        if (!is_string($method) || !preg_match('/^[!#$%&\'*+.^_`\|~0-9a-z-]+$/i', $method)) {
            return false;
        }

        $this->method = $method;

        return true;
    }

    /**
     * Retorna a URI da requisição.
     *
     * @return Uri URI da requisição.
     */
    public function getUri(): Uri
    {
        return $this->uri;
    }

    /**
     * Retorna uma cópia da requisição definindo uma nova URI.
     *
     * @param string|Uri $uri URI da requisição.
     * @param bool $preserveHost Define se deverá preservar o cabeçalho Host.
     * @return Request|null Cópia da requisição com a nova URI, nulo em caso de falha.
     */
    public function withUri($uri, $preserveHost = false): ?Request
    {
        /**
         * @var Request $new
         */
        $new = clone $this;

        if (!$new->setUri($uri)) {
            return null;
        }

        if (($preserveHost && $new->hasHeader('Host')) || ($uri->getHost() === '')) {
            return $new;
        }

        $host = $uri->getHost();

        if (!is_null($uri->getPort())) {
            $host .= ":" . $uri->getPort();
        }

        $new = $new->withHeader('Host', $host);

        return $new;
    }

    /**
     * Define a nova URI da requisição.
     *
     * @param string|Uri $uri Nova URI da requisição.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setUri($uri): bool
    {
        if (!is_string($uri) && !($uri instanceof Uri)) {
            return false;
        }

        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        $this->uri = $uri;

        return true;
    }

    /**
     * Retorna o valor de Host a partir da URI da requisição.
     *
     * @return string Host a partir da URI da requisição.
     */
    protected function getHostFromUri(): string
    {
        $host = $this->uri->getHost();
        $host .= $this->uri->getPort() ? ':' . $this->uri->getPort() : '';

        return $host;
    }


}