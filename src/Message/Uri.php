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

class Uri
{
    /**
     * @const string
     */
    const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    /**
     * @const string
     */
    const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~\pL';

    /**
     * @var int[]
     */
    protected $allowedSchemes = [
        'http' => 80,
        'https' => 443,
    ];

    /**
     * @var string
     */
    private $scheme = '';

    /**
     * @var string
     */
    private $userInfo = '';

    /**
     * @var string
     */
    private $host = '';

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var string
     */
    private $query = '';

    /**
     * @var string
     */
    private $fragment = '';

    /**
     * Inicializa uma nova instância de Uri.
     *
     * @param string $uri Uri como string.
     * @throws InvalidArgumentException Quando $uri for inválido.
     */
    public function __construct($uri)
    {
        if (false === $this->parseUri($uri)) {
            throw new InvalidArgumentException(sprintf(
                'Valor de $uri inválido: %s',
                $uri
            ));
        }
    }

    /**
     * Analisa a URI gerando as partes do objeto.
     *
     * @param string $uri Uri como string a ser analisada
     * @return bool Verdadeiro em caso de sucesos, falso caso contrário.
     */
    private function parseUri($uri): bool
    {
        if (!is_string($uri) || empty($uri)) {
            return false;
        }

        $parts = parse_url($uri);

        if (false === $parts) {
            return false;
        }

        if (
            !$this->setScheme($parts['scheme'] ?? '')
            || !$this->setUserInfo($parts['user'] ?? '', $parts['pass'] ?? null)
            || !$this->setHost($parts['host'] ?? '')
            || !$this->setPort($parts['port'] ?? null)
            || !$this->setPath($parts['path'] ?? '')
            || !$this->setQuery($parts['query'] ?? '')
            || !$this->setFragment($parts['fragment'] ?? '')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Retorna o esquema da URI.
     *
     * @return string Esquema da URI.
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Define o esquema da URI.
     *
     * @param string $scheme Nome do esquema.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setScheme($scheme): bool
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);

        if (!empty($scheme) && !isset($this->allowedSchemes[$scheme])) {
            return false;
        }

        $this->scheme = $scheme;

        return true;
    }

    /**
     * Retorna uma cópia da URI definindo o esquema.
     *
     * @param string $scheme Novo esquema da URI.
     * @return Uri|null Cópia da URI com o novo esquema, nulo em caso de falha.
     */
    public function withScheme($scheme): ?Uri
    {
        if (!is_string($scheme)) {
            return null;
        }

        $new = clone $this;

        if (!$new->setScheme($scheme)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna as informações de usuário da URI.
     *
     * @return string Informações do usuário.
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * Define as informações de usuário da URI.
     *
     * @param string $user Nome do usuário.
     * @param string|null $password Senha do usuário.
     * @return bool Verdadeiro em caso de sucesso, falho caso contrário.
     */
    protected function setUserInfo($user, $password = null): bool
    {
        if (!is_string($user)) {
            return false;
        }

        if (!is_null($password) && !is_string($password)) {
            return false;
        }

        $userInfo = preg_replace_callback(
            '/(?:[^%' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ']+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $user
        );

        if (!is_null($password)) {
            $userInfo .= ':' . preg_replace_callback(
                    '/(?:[^%' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ']+|%(?![A-Fa-f0-9]{2}))/u',
                    [$this, 'urlEncodeChar'],
                    $password
                );;
        }

        $this->userInfo = $userInfo;

        return true;
    }

    /**
     * Retorna uma cópia da URI definindo as informações de usuário.
     *
     * @param string $user Nome do usuário.
     * @param string|null $password Senha do usuário.
     * @return Uri|null Cópia da URI com as novas informações de usuário, nulo em caso de falha.
     */
    public function withUserInfo($user, $password = null): ?Uri
    {
        if (!is_string($user)) {
            return null;
        }

        if (!is_null($password) && !is_string($password)) {
            return null;
        }

        $new = clone $this;

        if (!$new->setUserInfo($user, $password)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna o host da URI.
     *
     * @return string Host da URI.
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Define o host da URI.
     *
     * @param string $host Novo host da URI.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setHost($host): bool
    {
        if (!is_string($host)) {
            return false;
        }

        $this->host = strtolower($host);

        return true;
    }

    /**
     * Retorna uma cópia da URI definindo o host.
     *
     * @param string $host Novo host da URI.
     * @return Uri|null Cópia da URI com o novo host, nulo em caso de falha.
     */
    public function withHost($host): ?Uri
    {
        if (!is_string($host)) {
            return null;
        }

        $new = clone $this;

        if (!$new->setHost($host)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna o número da porta.
     *
     * @return int|null Número da porta, nulo em caso de falha.
     */
    public function getPort(): ?int
    {
        return $this->isNonStandardPort($this->scheme, $this->host, $this->port)
            ? $this->port
            : null;
    }

    /**
     * Define a porta da URI.
     *
     * @param int|null $port Porta da URI.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setPort($port): bool
    {
        if (!is_null($port) && (!is_int($port) || $port < 1 || $port > 65535)) {
            return false;
        }

        $this->port = $port;

        return true;
    }

    /**
     * Verifica se a porta especificada é a porta padrão para o esquema.
     *
     * @param string $scheme Esquema da URI.
     * @param string $host Host da URI.
     * @param int|null $port Porta utilizada.
     * @return bool Verdadeiro se a porta não for padrão, falso caso contrário.
     */
    private function isNonStandardPort($scheme, $host, $port): bool
    {
        if ('' === $scheme) {
            return '' === $host || null !== $port;
        }

        if ('' === $host || null === $port) {
            return false;
        }

        return !isset($this->allowedSchemes[$scheme]) || $port !== $this->allowedSchemes[$scheme];
    }

    /**
     * Retorna uma cópia da URI definindo a porta.
     *
     * @param int|null $port Porta da URI.
     * @return Uri|null Cópia da URI com a nova porta, nulo em caso de falha.
     */
    public function withPort($port): ?Uri
    {
        $new = clone $this;

        if (!$new->setPort($port)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna uma cópia da URI definindo o caminho.
     *
     * @param string $path Caminho da URI.
     * @return Uri|null Cópia da URI com o novo caminho, nulo em caso de falha.
     */
    public function withPath($path): ?Uri
    {
        $new = clone $this;

        if (!$new->setPath($path)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna o segmento de busca da URI.
     *
     * @return string Segmento de busca da URI.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    protected function setQuery($query): bool
    {
        if (
            !is_string($query)
            || strpos($query, '#') !== false
        ) {
            return false;
        }

        if ('' !== $query && strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }

        $parts = explode('&', $query);

        foreach ($parts as $index => $part) {
            [$key, $value] = $this->splitQueryValue($part);

            if ($value === null) {
                $parts[$index] = $this->filterQueryOrFragment($key);
                continue;
            }

            $parts[$index] = sprintf(
                '%s=%s',
                $this->filterQueryOrFragment($key),
                $this->filterQueryOrFragment($value)
            );
        }

        $this->query = implode('&', $parts);

        return true;
    }

    /**
     * Retorna uma cópia de URI definindo o segmento de busca.
     *
     * @param string $query Segmento de busca.
     * @return Uri|null Cópia da URI com o novo segmento de busca, nulo em caso de falha.
     */
    public function withQuery($query): ?Uri
    {
        $new = clone $this;

        if (!$new->setQuery($query)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna o fragmento da URI.
     *
     * @return string Fragmento da URI.
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    protected function setFragment($fragment): bool
    {
        if (!is_string($fragment)) {
            return false;
        }

        if ('' !== $fragment && strpos($fragment, '#') === 0) {
            $fragment = '%23' . substr($fragment, 1);
        }

        $this->fragment = $this->filterQueryOrFragment($fragment);

        return true;
    }

    /**
     * Retorna uma cópia da URI definindo o fragmento.
     *
     * @param string $fragment Fragmento da URI.
     * @return Uri|null Cópia da URI com o novo fragmento, nulo em caso de falha.
     */
    public function withFragment($fragment): ?Uri
    {
        $new = clone $this;

        if (!$new->setFragment($fragment)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna a representação como string da URI.
     *
     * @return string Representação da URI.
     */
    public function __toString(): string
    {
        return static::createUriString(
            $this->scheme,
            $this->getAuthority(),
            $this->getPath(),
            $this->query,
            $this->fragment
        );
    }

    /**
     * Retorna a URI como string.
     *
     * @param string $scheme Esquema da URI.
     * @param string $authority Autoridade da URI.
     * @param string $path Caminho da URI.
     * @param string $query Segmento de busca da URI.
     * @param string $fragment Fragmento da URI.
     * @return string Representação da URI como string.
     */
    private static function createUriString($scheme, $authority, $path, $query, $fragment): string
    {
        $uri = '';

        if ('' !== $scheme) {
            $uri .= sprintf('%s:', $scheme);
        }

        if ('' !== $authority) {
            $uri .= '//' . $authority;
        }

        if ('' !== $path && '/' !== substr($path, 0, 1)) {
            $path = '/' . $path;
        }

        $uri .= $path;

        if ('' !== $query) {
            $uri .= sprintf('?%s', $query);
        }

        if ('' !== $fragment) {
            $uri .= sprintf('#%s', $fragment);
        }

        return $uri;
    }

    /**
     * Retorna a autoridade da URI.
     *
     * @return string Autoridade da URI.
     */
    public function getAuthority(): string
    {
        if ('' === $this->host) {
            return '';
        }

        $authority = $this->host;

        if ('' !== $this->userInfo) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->isNonStandardPort($this->scheme, $this->host, $this->port)) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * Retorna o caminho da URI.
     *
     * @return string Caminho da URI.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Define o novo caminho da URI.
     *
     * @param string $path Caminho da URI.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setPath($path): bool
    {
        if (
            !is_string($path)
            || strpos($path, '?') !== false
            || strpos($path, '#') !== false
        ) {
            return false;
        }

        $path = preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . ')(:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $path
        );

        $this->path = !empty($path) ? '/' . ltrim($path, '/') : '';

        return true;
    }

    /**
     * Separa um segmento de busca em nome e valor.
     *
     * @param string $value Valor do segmento de busca.
     * @return array Array com dois valores, nome e valor.
     */
    private function splitQueryValue($value): array
    {
        $data = explode('=', $value, 2);

        if (!isset($data[1])) {
            $data[] = null;
        }

        return $data;
    }

    /**
     * Filtra um valor que compõe o segmento de busca ou fragmento.
     *
     * @param string $value String a ser filtrada.
     * @return string String após aplicar o filtro.
     */
    private function filterQueryOrFragment($value): string
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $value
        );
    }

    /**
     * Codifica os caracteres a partir de um array de filtro.
     *
     * @param array $matches Array gerado pelo filtro.
     * @return string String codificada.
     */
    private function urlEncodeChar($matches): string
    {
        return rawurlencode($matches[0]);
    }
}