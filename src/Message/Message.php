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
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class Message implements MessageInterface
{
    /**
     * @var string
     */
    private $protocol = '1.1';

    /**
     * @var string[][]
     */
    private $headers = [];

    /**
     * @var StreamInterface
     */
    private $body;

    /**
     * @param string $body
     * @param array $headers
     */
    protected function __construct($body = 'php://memory', array $headers = [])
    {
        $this->setBody($body, 'wb+');
        $this->setHeaders($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * @param $version
     * @return $this
     */
    protected function setProtocolVersion($version)
    {
        $this->protocol = $version;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version)
    {
        $new = clone $this;
        $new->setProtocolVersion($version);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {

        return $this->headers;
    }

    /**
     * @param array $originalHeaders
     * @return static
     */
    protected function setHeaders(array $originalHeaders)
    {
        foreach ($originalHeaders as $name => $value) {
            $this->setHeader($name, $value);
        }

        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return static
     */
    protected function setHeader($name, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        $name = $this->normalizeHeaderName($name);

        foreach ($value as $v) {
            if (!is_string($v) && !is_numeric($v)) {
                throw new InvalidArgumentException(sprintf(
                    'Tipo de valor de cabeçalho inválido; esperado string ou numérico; recebido %s',
                    (is_object($v) ? get_class($v) : gettype($v))
                ));
            }

            $valid = true;

            if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $v)) {
                $valid = false;
            }

            if (preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $v)) {
                $valid = false;
            }

            if (!$valid) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" não é um valor válido de cabeçalho',
                    gettype($value)
                ));
            }
        }

        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name): string
    {
        if (!$this->hasHeader($name)) {
            return '';
        }

        return implode(', ', $this->getHeader($name));
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name): bool
    {
        $name = $this->normalizeHeaderName($name);

        return array_key_exists($name, $this->headers);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        $name = $this->normalizeHeaderName($name);

        return $this->headers[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        if ($this->hasHeader($name)) {
            $value = array_merge($this->getHeader($name), $value);
        }

        return $this->withHeader($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        $new = clone $this;
        $new->setHeader($name, $value);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        $new = clone $this;

        if ($new->hasHeader($name)) {
            $name = $this->normalizeHeaderName($name);
            unset($new->headers[$name]);
        }

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @param string|resource|StreamInterface $body
     * @param string $mode
     * @return static
     */
    protected function setBody($body, string $mode = 'r')
    {
        if (!is_string($body) && !is_resource($body) && !($body instanceof StreamInterface)) {
            throw new InvalidArgumentException();
        }

        if (is_string($body) || is_resource($body)) {
            $body = new Stream($body, $mode);
        }

        $this->body = $body;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        $new = clone $this;
        $new->setBody($body);

        return $new;
    }

    /**
     * @param $name
     * @return string
     */
    private function normalizeHeaderName($name): string
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException(sprintf(
                'Tipo do nome de cabeçalho inválido; esperado string, recebido %s',
                (is_object($name) ? get_class($name) : gettype($name))
            ));
        }

        if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" não é um nome válido de cabeçalho',
                $name
            ));
        }

        return implode('-', array_map('ucfirst', explode('-', $name)));
    }
}