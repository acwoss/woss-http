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
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    private $serverParams;

    /**
     * @var array
     */
    private $cookieParams;

    /**
     * @var array
     */
    private $queryParams;

    /**
     * @var array
     */
    private $uploadedFiles;

    /**
     * @var null|array|object
     */
    private $parsedBody;

    /**
     * @var array
     */
    private $attributes;

    public function __construct(
        array $serverParams = [],
        array $uploadedFiles = [],
        $uri = null,
        string $method = null,
        $body = 'php://input',
        array $headers = [],
        array $cookies = [],
        array $queryParams = [],
        $parsedBody = null,
        string $protocol = '1.1'
    )
    {
        parent::__construct($uri, $method, $body, $headers);

        $this->setServerParams($serverParams);
        $this->setUploadedFiles($uploadedFiles);
        $this->setCookieParams($cookies);
        $this->setQueryParams($queryParams);
        $this->setParsedBody($parsedBody);
        $this->setProtocolVersion($protocol);
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $this->assertUploadedFiles($uploadedFiles);

        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data)
    {
        if (!is_array($data) && !is_object($data) && null !== $data) {
            throw new InvalidArgumentException(sprintf(
                '%s expects a null, array, or object argument; received %s',
                __METHOD__,
                gettype($data)
            ));
        }

        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($name, $value)
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($name)
    {
        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    protected function assertUploadedFiles(array $uploadedFiles)
    {
        foreach ($uploadedFiles as $key => $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                throw new InvalidArgumentException(sprintf(
                    "Objeto não suportado na posição %d. Era esperado uma instância de UploadedFile, mas chegou %s",
                    $key,
                    is_object($uploadedFile) ? get_class($uploadedFile) : gettype($uploadedFile)
                ));
            }
        }
    }

    /**
     * @param array $serverParams
     * @return static
     */
    protected function setServerParams(array $serverParams)
    {
        $this->serverParams = $serverParams;

        return $this;
    }

    /**
     * @param array $uploadedFiles
     * @return static
     */
    protected function setUploadedFiles(array $uploadedFiles)
    {
        $this->uploadedFiles = $uploadedFiles;

        return $this;
    }

    /**
     * @param array $cookieParams
     * @return static
     */
    protected function setCookieParams(array $cookieParams)
    {
        $this->cookieParams = $cookieParams;

        return $this;
    }

    /**
     * @param array $queryParams
     * @return static
     */
    protected function setQueryParams(array $queryParams)
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    /**
     * @param array|object|null $parsedBody
     * @return static
     */
    protected function setParsedBody($parsedBody)
    {
        $this->parsedBody = $parsedBody;

        return $this;
    }
}