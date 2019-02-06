<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

declare(strict_types=1);

namespace Woss\Http\Message;

class ServerRequest extends Request
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

    /**
     * Inicializa uma nova instância de ServerRequest.
     *
     * @param array $serverParams Lista de parâmetros do servidor.
     * @param array $uploadedFiles Lista de arquivos enviados pela requisição.
     * @param string|Uri $uri URI da requisição.
     * @param string $method Método da requisição.
     * @param string|resource|Stream $body Corpo da requisição.
     * @param array $headers Lista de cabeçalhos da requisição.
     * @param array $cookies Lista de cookies da requisição.
     * @param array $queryParams Lista de parâmetros de busca da requisição.
     * @param array|null $parsedBody Corpo da requisição já processado.
     * @param string $protocol Versão do protocolo da requisição.
     */
    public function __construct(
        $serverParams = [],
        $uploadedFiles = [],
        $uri = '',
        $method = 'GET',
        $body = 'php://input',
        $headers = [],
        $cookies = [],
        $queryParams = [],
        $parsedBody = null,
        $protocol = '1.1'
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
     * Retorna a lista de parâmetros do servidor.
     *
     * @return array Lista de parâmetros do servidor.
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * Define os novos parâmetros de servidor da requisição.
     *
     * @param array $serverParams Lista de novos parâmetros da requisição.
     * @return bool Verdadeiro em caso de sucesso, falso em caso contrário.
     */
    protected function setServerParams($serverParams): bool
    {
        if (!is_array($serverParams)) {
            return false;
        }

        $this->serverParams = $serverParams;

        return true;
    }

    /**
     * Retorna uma cópia da requisição definindo os novos parãmetros do servidor.
     *
     * @param array $serverParams Lista dos novos parãmetros do servidor.
     * @return ServerRequest|null Cópia da requisição com os novos parâmetros, nulo em caso de falha.
     */
    public function withServerParams($serverParams): ?ServerRequest
    {
        $new = clone $this;

        if (!$new->setServerParams($serverParams)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna a lista de cookies da requisição.
     *
     * @return array Lista de cookies da requisição.
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * Define a lista de cookies da requisição.
     *
     * @param array $cookieParams Lista de novos cookies da requisição.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setCookieParams($cookieParams): bool
    {
        if (!is_array($cookieParams)) {
            return false;
        }

        $this->cookieParams = $cookieParams;

        return true;
    }

    /**
     * Retorna uma cópia da requisição definindo os novos cookies.
     *
     * @param array $cookieParams Lista dos novos cookies da requisição.
     * @return ServerRequest|null Cópia da requisição com os novos cookies, nulo em caso de falha.
     */
    public function withCookieParams($cookieParams): ?ServerRequest
    {
        $new = clone $this;

        if (!$new->setCookieParams($cookieParams)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna a lista de parâmetros de busca da requisição.
     *
     * @return array Lista de parâmetros de busca da requisição.
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * Define novos parâmetros de busca para a requisição.
     *
     * @param array $queryParams Lista de novos parâmetros de busca.
     * @return bool Verdadeiro em caso de sucesso, falha caso contrário.
     */
    protected function setQueryParams($queryParams): bool
    {
        if (!is_array($queryParams)) {
            return false;
        }

        $this->queryParams = $queryParams;

        return true;
    }

    /**
     * Retorna uma cópia da requisição definindo novos parâmetros de busca.
     *
     * @param array $queryParams Lista dos novos parâmetros de busca.
     * @return ServerRequest|null Cópia da requisição com os novos parâmetros de busca, nulo em caso de falha.
     */
    public function withQueryParams($queryParams): ?ServerRequest
    {
        $new = clone $this;

        if (!$new->setQueryParams($queryParams)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna a lista de arquivos enviados pela requisição.
     *
     * @return array Lista de arquivos enviados pela requisição.
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * Define novos arquivos enviados pela requisição.
     *
     * @param array $uploadedFiles Lista com novos arquivos enviados pela requisição.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setUploadedFiles($uploadedFiles): bool
    {
        if (!is_array($uploadedFiles)) {
            return false;
        }

        foreach ($uploadedFiles as $key => $uploadedFile) {
            if (is_array($uploadedFile)) {
                foreach ($uploadedFile as $file) {
                    if (!($file instanceof UploadedFile)) {
                        return false;
                    }
                }
            } else if (!($uploadedFile instanceof UploadedFile)) {
                return false;
            }
        }

        $this->uploadedFiles = $uploadedFiles;

        return true;
    }

    /**
     * Retorna uma cópia da requisição definindo novos arquivos enviados.
     *
     * @param array $uploadedFiles Lista de novos arquivos enviados pela requisição.
     * @return ServerRequest|null Cópia da requisição com os novos arquivos, nulo em caso de falha.
     */
    public function withUploadedFiles($uploadedFiles): ?ServerRequest
    {
        $new = clone $this;

        if (!$new->setUploadedFiles($uploadedFiles)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna o corpo da requisição já processado.
     *
     * @return array|object|null Corpo da requisição já processado.
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * Define um novo corpo processado para a requisição.
     *
     * @param array|object|null $parsedBody Novo corpo processado para a requisição.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setParsedBody($parsedBody): bool
    {
        if (!is_array($parsedBody) && !is_object($parsedBody) && !is_null($parsedBody)) {
            return false;
        }

        $this->parsedBody = $parsedBody;

        return true;
    }

    /**
     * Retorna uma cópia da requisição definindo o novo corpo processado.
     *
     * @param array|object|null $parsedBody Novo corpo processado para a requisição.
     * @return ServerRequest|null Cópia da requisição com o novo corpo processado, nulo em caso de falha.
     */
    public function withParsedBody($parsedBody)
    {
        $new = clone $this;

        if (!$new->setParsedBody($parsedBody)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna a lista de atributos da requisição.
     *
     * @return array Lista de atributos da requisição.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Retorna o valor de um atributo da requisição.
     *
     * @param mixed $name Nome do atributo da requisição.
     * @param mixed $default Valor a ser retornado quando o atributo não for encontrado.
     * @return mixed Valor do atributo da requisição.
     */
    public function getAttribute($name, $default = null)
    {
        if (!$this->hasAttribute($name)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * Verifica se a requisição um atributo.
     *
     * @param mixed $name Nome do atributo da requisição.
     * @return bool Verdadeiro se a requisição possuir o atributo, falso em caso contrário.
     */
    public function hasAttribute($name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Define o atributo da requisição.
     *
     * @param mixed $name Nome do atributo da requisição.
     * @param mixed $value Valor do atributo da requisição.
     * @return bool Verdadeiro em caso de sucesso, false caso contrário.
     */
    protected function setAttribute($name, $value): bool
    {
        $this->attributes[$name] = $value;

        return true;
    }

    /**
     * Retorna uma cópia da requisição definindo o novo atributo.
     *
     * @param mixed $name Nome do atributo da requisição.
     * @param mixed $value Valor do atributo da requisição.
     * @return ServerRequest|null Cópia da requisição com o novo atributo, nulo em caso de falha.
     */
    public function withAttribute($name, $value): ?ServerRequest
    {
        $new = clone $this;

        if (!$new->setAttribute($name, $value)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna uma cópia da requisição sem o atributo especificado.
     *
     * @param mixed $name Nome do atributo da requisição.
     * @return ServerRequest|null Cópia da requisição sem o atributo, nulo em caso de falha.
     */
    public function withoutAttribute($name)
    {
        $new = clone $this;

        if ($new->hasAttribute($name)) {
            unset($new->attributes[$name]);
        }

        return $new;
    }

    /**
     * Cria uma requisição a partir das variáveis super globais no servidor.
     *
     * @return ServerRequest Requisição gerada a partir das variáveis super globais.
     */
    public static function fromGlobals(): ServerRequest
    {
        $getAllHeaders = function_exists('getallheaders') ? 'getallheaders' : function ($server) {
            $headers = [];
            foreach ($server as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $name = str_replace(
                        ' ',
                        '-',
                        ucwords(
                            strtolower(
                                str_replace(
                                    '_',
                                    ' ',
                                    substr($name, 5)
                                )
                            )
                        )
                    );

                    $headers[$name] = $value;
                }
            }

            return $headers;
        };

        $headers = call_user_func($getAllHeaders, $server ?? $_SERVER);

        return new static(
            $_SERVER,
            UploadedFile::createFromArray($_FILES),
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            'php://input',
            $headers,
            $_COOKIE,
            $_GET,
            $_POST,
            $_SERVER['SERVER_PROTOCOL']
        );
    }
}