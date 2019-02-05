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
use Psr\Http\Message\UriInterface;
use function explode;
use function implode;
use function is_numeric;
use function is_string;
use function ltrim;
use function parse_url;
use function preg_replace;
use function preg_replace_callback;
use function rawurlencode;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;

class Uri implements UriInterface
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
     * @var string|null
     */
    private $uriString;

    public function __construct(string $uri = '')
    {
        if ('' === $uri) {
            return;
        }

        $this->parseUri($uri);
    }

    /**
     * Operations to perform on clone.
     *
     * Since cloning usually is for purposes of mutation, we reset the
     * $uriString property so it will be re-calculated.
     */
    public function __clone()
    {
        $this->uriString = null;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        if (null !== $this->uriString) {
            return $this->uriString;
        }

        $this->uriString = static::createUriString(
            $this->scheme,
            $this->getAuthority(),
            $this->getPath(),
            $this->query,
            $this->fragment
        );

        return $this->uriString;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * {@inheritdoc}
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * {@inheritdoc}
     */
    public function getPort(): ?int
    {
        return $this->isNonStandardPort($this->scheme, $this->host, $this->port)
            ? $this->port
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * {@inheritdoc}
     */
    public function withScheme($scheme): UriInterface
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException();
        }

        $scheme = $this->filterScheme($scheme);

        if ($scheme === $this->scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withUserInfo($user, $password = null): UriInterface
    {
        if (!is_string($user)) {
            throw new InvalidArgumentException();
        }

        if (null !== $password && !is_string($password)) {
            throw new InvalidArgumentException();
        }

        $info = $this->filterUserInfoPart($user);

        if (null !== $password) {
            $info .= ':' . $this->filterUserInfoPart($password);
        }

        if ($info === $this->userInfo) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withHost($host): UriInterface
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException();
        }

        if ($host === $this->host) {
            return $this;
        }

        $new = clone $this;
        $new->host = strtolower($host);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPort($port): UriInterface
    {
        if ($port !== null) {
            if (!is_numeric($port) || is_float($port)) {
                throw new InvalidArgumentException();
            }

            $port = (int)$port;
        }

        if ($port === $this->port) {
            return $this;
        }

        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException();
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withPath($path): UriInterface
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException();
        }

        if (strpos($path, '?') !== false) {
            throw new InvalidArgumentException();
        }

        if (strpos($path, '#') !== false) {
            throw new InvalidArgumentException();
        }

        $path = $this->filterPath($path);

        if ($path === $this->path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery($query): UriInterface
    {
        if (!is_string($query)) {
            throw new InvalidArgumentException();
        }

        if (strpos($query, '#') !== false) {
            throw new InvalidArgumentException();
        }

        $query = $this->filterQuery($query);

        if ($query === $this->query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withFragment($fragment): UriInterface
    {
        if (!is_string($fragment)) {
            throw new InvalidArgumentException();
        }

        $fragment = $this->filterFragment($fragment);

        if ($fragment === $this->fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * @param string $uri
     */
    private function parseUri(string $uri): void
    {
        $parts = parse_url($uri);

        if (false === $parts) {
            throw new InvalidArgumentException();
        }

        $this->scheme = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
        $this->userInfo = isset($parts['user']) ? $this->filterUserInfoPart($parts['user']) : '';
        $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $this->port = isset($parts['port']) ? $parts['port'] : null;
        $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
        $this->query = isset($parts['query']) ? $this->filterQuery($parts['query']) : '';
        $this->fragment = isset($parts['fragment']) ? $this->filterFragment($parts['fragment']) : '';

        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    /**
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @return string
     */
    private static function createUriString(
        string $scheme,
        string $authority,
        string $path,
        string $query,
        string $fragment
    ): string
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
     * @param string $scheme
     * @param string $host
     * @param int|null $port
     * @return bool
     */
    private function isNonStandardPort(string $scheme, string $host, ?int $port): bool
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
     * @param string $scheme Scheme name.
     * @return string Filtered scheme.
     */
    private function filterScheme(string $scheme): string
    {
        $scheme = strtolower($scheme);
        $scheme = preg_replace('#:(//)?$#', '', $scheme);

        if ('' === $scheme) {
            return '';
        }

        if (!isset($this->allowedSchemes[$scheme])) {
            throw new InvalidArgumentException();
        }

        return $scheme;
    }

    /**
     * @param string $part
     * @return string
     */
    private function filterUserInfoPart(string $part): string
    {
        return preg_replace_callback(
            '/(?:[^%' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . ']+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $part
        );
    }

    /**
     * @param string $path
     * @return string
     */
    private function filterPath(string $path): string
    {
        $path = preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . ')(:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $path
        );

        if ('' === $path) {
            return $path;
        }

        if ($path[0] !== '/') {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }

    /**
     * @param string $query
     * @return string
     */
    private function filterQuery(string $query): string
    {
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

        return implode('&', $parts);
    }

    /**
     * @param string $value
     * @return array A value with exactly two elements, key and value
     */
    private function splitQueryValue(string $value): array
    {
        $data = explode('=', $value, 2);

        if (!isset($data[1])) {
            $data[] = null;
        }

        return $data;
    }

    /**
     * @param string $fragment
     * @return string
     */
    private function filterFragment(string $fragment): string
    {
        if ('' !== $fragment && strpos($fragment, '#') === 0) {
            $fragment = '%23' . substr($fragment, 1);
        }

        return $this->filterQueryOrFragment($fragment);
    }

    /**
     * @param string $value
     * @return string
     */
    private function filterQueryOrFragment(string $value): string
    {
        return preg_replace_callback(
            '/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/u',
            [$this, 'urlEncodeChar'],
            $value
        );
    }

    /**
     * @param array $matches
     * @return string
     */
    private function urlEncodeChar(array $matches): string
    {
        return rawurlencode($matches[0]);
    }
}