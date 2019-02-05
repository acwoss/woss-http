<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

declare(strict_types=1);

namespace Woss\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{
    /**
     * @var string
     */
    private $requestTarget;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var string
     */
    private $method;

    /**
     * @param null|string|UriInterface $uri
     * @param null|string $method
     * @param string|resource|StreamInterface $body
     * @param array $headers Headers
     * @throws InvalidArgumentException
     */
    public function __construct($uri = null, string $method = null, $body = 'php://temp', array $headers = [])
    {
        if ($method !== null) {
            $this->setMethod($method);
        }

        parent::__construct($body, $headers);
        $this->uri = $this->createUri($uri);

        if (!$this->hasHeader('Host') && $this->uri->getHost()) {
            $this->setHeader('Host', $this->getHostFromUri());
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget): RequestInterface
    {
        // TODO: fazer a validação do alvo de requisição
        $new = clone $this;
        $new->requestTarget = $requestTarget;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method): RequestInterface
    {
        // TODO: fazer a validação do método HTTP
        $new = clone $this;
        $new->method = $method;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * {@inheritdoc}
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

    /**
     * @param string $method
     * @return static
     * @throws InvalidArgumentException
     */
    protected function setMethod($method)
    {
        if (!is_string($method)) {
            throw new InvalidArgumentException();
        }

        if (!preg_match('/^[!#$%&\'*+.^_`\|~0-9a-z-]+$/i', $method)) {
            throw new InvalidArgumentException();
        }

        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    protected function getHostFromUri(): string
    {
        $host = $this->uri->getHost();
        $host .= $this->uri->getPort() ? ':' . $this->uri->getPort() : '';

        return $host;
    }

    /**
     * @param $uri
     * @return UriInterface
     */
    protected function createUri($uri): UriInterface
    {
        if ($uri instanceof UriInterface) {
            return $uri;
        }

        if (is_string($uri)) {
            return new Uri($uri);
        }

        if ($uri === null) {
            return new Uri();
        }

        throw new InvalidArgumentException();
    }
}