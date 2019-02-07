<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Woss\Http\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Woss\Http\Message\Uri;

class UriTest extends TestCase
{
    const URL = 'https://user:pass@local.example.com:8042/over/there;param=value;p2;p3?name=ferret#nose';

    /**
     * @var Uri
     */
    private $uri;

    public function setUp(): void
    {
        $this->uri = $uri = new Uri(self::URL);
    }

    public function testConstructorSetsAllProperties()
    {
        $this->assertSame('https', $this->uri->getScheme());
        $this->assertSame('user:pass', $this->uri->getUserInfo());
        $this->assertSame('local.example.com', $this->uri->getHost());
        $this->assertSame(8042, $this->uri->getPort());
        $this->assertSame('user:pass@local.example.com:8042', $this->uri->getAuthority());
        $this->assertSame('/over/there;param=value;p2;p3', $this->uri->getPath());
        $this->assertSame('name=ferret', $this->uri->getQuery());
        $this->assertSame('nose', $this->uri->getFragment());
    }

    public function testCanSerializeToString()
    {
        $this->assertSame(self::URL, (string)$this->uri);
    }

    public function testWithSchemeReturnsNewInstanceWithNewScheme()
    {
        $new = $this->uri->withScheme('http');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('https', $this->uri->getScheme());
        $this->assertSame('http', $new->getScheme());
        $this->assertSame('http://user:pass@local.example.com:8042/over/there;param=value;p2;p3?name=ferret#nose', (string)$new);
    }

    public function testWithSchemeReturnsNewInstanceWithSameScheme()
    {
        $new = $this->uri->withScheme('https');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('https', $this->uri->getScheme());
        $this->assertSame('https', $new->getScheme());
        $this->assertSame('https://user:pass@local.example.com:8042/over/there;param=value;p2;p3?name=ferret#nose', (string)$new);
    }

    public function testWithUserInfoReturnsNewInstanceWithProvidedUser()
    {
        $new = $this->uri->withUserInfo('woss');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('user:pass', $this->uri->getUserInfo());
        $this->assertSame('woss', $new->getUserInfo());
        $this->assertSame('https://woss@local.example.com:8042/over/there;param=value;p2;p3?name=ferret#nose', (string)$new);
    }

    public function testWithUserInfoReturnsNewInstanceWithProvidedUserAndPassword()
    {
        $new = $this->uri->withUserInfo('woss', 'http');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('user:pass', $this->uri->getUserInfo());
        $this->assertSame('woss:http', $new->getUserInfo());
        $this->assertSame('https://woss:http@local.example.com:8042/over/there;param=value;p2;p3?name=ferret#nose', (string)$new);
    }

    public function testWithUserInfoReturnsNullIfPasswordIsNotString()
    {
        $new = $this->uri->withUserInfo('woss', 42);

        $this->assertNull($new);
    }

    public function testWithUserInfoReturnsNewInstanceIfUserAndPasswordAreSameAsBefore()
    {
        $new = $this->uri->withUserInfo('user', 'pass');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('user:pass', $this->uri->getUserInfo());
        $this->assertSame('user:pass', $new->getUserInfo());
        $this->assertSame(self::URL, (string)$new);
    }

    public function userInfoProvider()
    {
        return [
            // name => [user, password, expected]
            'valid-chars' => ['foo', 'bar', 'foo:bar'],
            'colon' => ['foo:bar', 'baz:bat', 'foo%3Abar:baz%3Abat'],
            'at' => ['user@example.com', 'cred@foo', 'user%40example.com:cred%40foo'],
            'percent' => ['%25', '%25', '%25:%25'],
            'invalid-enc' => ['%ZZ', '%GG', '%25ZZ:%25GG'],
        ];
    }

    /**
     * @dataProvider userInfoProvider
     * @param string $user Nome de usuário
     * @param string $password Senha do usuário
     * @param string $expected Informações do usuário esperadas
     */
    public function testWithUserInfoEncodesUsernameAndPassword($user, $password, $expected)
    {
        $new = $this->uri->withUserInfo($user, $password);

        $this->assertSame($expected, $new->getUserInfo());
    }

    public function testWithHostReturnsNewInstanceWithProvidedHost()
    {
        $new = $this->uri->withHost('woss.eng.br');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('local.example.com', $this->uri->getHost());
        $this->assertSame('woss.eng.br', $new->getHost());
        $this->assertSame('https://user:pass@woss.eng.br:8042/over/there;param=value;p2;p3?name=ferret#nose', (string)$new);
    }

    public function testWithHostReturnsNewInstanceWithProvidedHostIsSameAsBefore()
    {
        $new = $this->uri->withHost('local.example.com');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('local.example.com', $this->uri->getHost());
        $this->assertSame('local.example.com', $new->getHost());
        $this->assertSame('https://user:pass@local.example.com:8042/over/there;param=value;p2;p3?name=ferret#nose', (string)$new);
    }

    public function validPorts()
    {
        return [
            'null' => [null],
            'int' => [3000],
        ];
    }

    /**
     * @dataProvider validPorts
     * @param int|null $port Número da porta
     */
    public function testWithPortReturnsNewInstanceWithProvidedPort($port)
    {
        $new = $this->uri->withPort($port);

        $this->assertNotSame($this->uri, $new);
        $this->assertEquals(8042, $this->uri->getPort());
        $this->assertEquals($port, $new->getPort());
        $this->assertSame(
            sprintf(
                'https://user:pass@local.example.com%s/over/there;param=value;p2;p3?name=ferret#nose',
                is_null($port) ? '' : (':' . $port)
            ),
            (string)$new
        );
    }

    public function testWithPortReturnsNewInstanceWithProvidedPortIsSameAsBefore()
    {
        $new = $this->uri->withPort(8042);

        $this->assertNotSame($this->uri, $new);
        $this->assertSame(8042, $this->uri->getPort());
        $this->assertSame(8042, $new->getPort());
    }

    public function invalidPorts()
    {
        return [
            'true' => [true],
            'false' => [false],
            'string' => ['string'],
            'numeric_string' => ['8042'],
            'float' => [55.5],
            'array' => [[3000]],
            'object' => [(object)['port' => 3000]],
            'zero' => [0],
            'too-small' => [-1],
            'too-big' => [65536],
        ];
    }

    /**
     * @dataProvider invalidPorts
     * @param int|null $port Número da porta.
     */
    public function testWithPortReturnsNullForInvalidPorts($port)
    {
        $new = $this->uri->withPort($port);

        $this->assertNull($new);
    }

    public function testWithPathReturnsNewInstanceWithProvidedPath()
    {
        $new = $this->uri->withPath('/bar/baz');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('/over/there;param=value;p2;p3', $this->uri->getPath());
        $this->assertSame('/bar/baz', $new->getPath());
        $this->assertSame('https://user:pass@local.example.com:8042/bar/baz?name=ferret#nose', (string)$new);
    }

    public function testWithPathReturnsNewInstanceWithProvidedPathSameAsBefore()
    {
        $new = $this->uri->withPath('/over/there;param=value;p2;p3');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('/over/there;param=value;p2;p3', $this->uri->getPath());
        $this->assertSame('/over/there;param=value;p2;p3', $new->getPath());
        $this->assertSame(self::URL, (string)$new);
    }

    public function invalidPaths()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'array' => [['/bar/baz']],
            'object' => [(object)['/bar/baz']],
            'query' => ['/bar/baz?bat=quz'],
            'fragment' => ['/bar/baz#bat'],
            'int' => [42],
            'float' => [2.6],
        ];
    }

    /**
     * @dataProvider invalidPaths
     * @param string $path Caminho da URI.
     */
    public function testWithPathReturnsNullForInvalidPaths($path)
    {
        $new = $this->uri->withPath($path);

        $this->assertNull($new);
    }

    public function testWithQueryReturnsNewInstanceWithProvidedQuery()
    {
        $new = $this->uri->withQuery('baz=bat');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('name=ferret', $this->uri->getQuery());
        $this->assertSame('baz=bat', $new->getQuery());
        $this->assertSame('https://user:pass@local.example.com:8042/over/there;param=value;p2;p3?baz=bat#nose', (string)$new);
    }

    public function invalidQueryStrings()
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'array' => [['baz=bat']],
            'object' => [(object)['baz=bat']],
            'fragment' => ['baz=bat#quz'],
            'int' => [42],
            'float' => [2.6],
        ];
    }

    /**
     * @dataProvider invalidQueryStrings
     * @param string $query Segmento de consulta da URI.
     */
    public function testWithQueryReturnsNullForInvalidQueryStrings($query)
    {
        $new = $this->uri->withQuery($query);

        $this->assertNull($new);
    }

    public function testWithFragmentReturnsNewInstanceWithProvidedFragment()
    {
        $new = $this->uri->withFragment('qat');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('nose', $this->uri->getFragment());
        $this->assertSame('qat', $new->getFragment());
        $this->assertSame('https://user:pass@local.example.com:8042/over/there;param=value;p2;p3?name=ferret#qat', (string)$new);
    }

    public function testWithFragmentReturnsNewInstanceWithProvidedFragmentSameAsBefore()
    {
        $new = $this->uri->withFragment('nose');

        $this->assertNotSame($this->uri, $new);
        $this->assertSame('nose', $this->uri->getFragment());
        $this->assertSame('nose', $new->getFragment());
        $this->assertSame('https://user:pass@local.example.com:8042/over/there;param=value;p2;p3?name=ferret#nose', (string)$new);
    }

    public function authorityInfo()
    {
        return [
            'host-only' => ['http://foo.com/bar', 'foo.com'],
            'host-port' => ['http://foo.com:3000/bar', 'foo.com:3000'],
            'user-host' => ['http://me@foo.com/bar', 'me@foo.com'],
            'user-host-port' => ['http://me@foo.com:3000/bar', 'me@foo.com:3000'],
        ];
    }

    /**
     * @dataProvider authorityInfo
     * @param string $url URL de teste.
     * @param string $expected Saída esperada.
     */
    public function testRetrievingAuthorityReturnsExpectedValues($url, $expected)
    {
        $uri = new Uri($url);
        $this->assertSame($expected, $uri->getAuthority());
    }

    public function testCanEmitOriginFormUrl()
    {
        $url = '/foo/bar?baz=bat';
        $uri = new Uri($url);

        $this->assertSame($url, (string)$uri);
    }

    public function testSettingEmptyPathOnAbsoluteUriReturnsAnEmptyPath()
    {
        $uri = new Uri('http://example.com/foo');

        $new = $uri->withPath('');

        $this->assertSame('', $new->getPath());
    }

    public function testStringRepresentationOfAbsoluteUriWithNoPathSetsAnEmptyPath()
    {
        $uri = new Uri('http://example.com');

        $this->assertSame('http://example.com', (string)$uri);
    }

    public function testEmptyPathOnOriginFormRemainsAnEmptyPath()
    {
        $uri = new Uri('?foo=bar');

        $this->assertSame('', $uri->getPath());
    }

    public function testStringRepresentationOfOriginFormWithNoPathRetainsEmptyPath()
    {
        $uri = new Uri('?foo=bar');

        $this->assertSame('?foo=bar', (string)$uri);
    }

    public function testConstructorRaisesExceptionForSeriouslyMalformedURI()
    {
        $this->expectException(InvalidArgumentException::class);
        new Uri('http:///www.php-fig.org/');
    }

    public function testMutatingSchemeStripsOffDelimiter()
    {
        $new = $this->uri->withScheme('https://');

        $this->assertSame('https', $new->getScheme());
    }

    public function testSchemeStripsOffDelimiter()
    {
        $new = $this->uri->withScheme('://');

        $this->assertSame('', $new->getScheme());
    }

    public function invalidSchemes()
    {
        return [
            'mailto' => ['mailto'],
            'ftp' => ['ftp'],
            'telnet' => ['telnet'],
            'ssh' => ['ssh'],
            'git' => ['git'],
        ];
    }

    /**
     * @dataProvider invalidSchemes
     * @param string $scheme Esquema a ser testado.
     */
    public function testConstructWithUnsupportedSchemeRaisesAnException($scheme)
    {
        $this->expectException(InvalidArgumentException::class);
        new Uri($scheme . '://example.com');
    }

    /**
     * @dataProvider invalidSchemes
     * @param string $scheme Esquema a ser testado.
     */
    public function testMutatingWithUnsupportedSchemeReturnsNull($scheme)
    {
        $new = $this->uri->withScheme($scheme);

        $this->assertNull($new);
    }

    public function testPathIsNotPrefixedWithSlashIfSetWithOne()
    {
        $new = $this->uri->withPath('foo/bar');

        $this->assertSame('/foo/bar', $new->getPath());
    }

    public function testPathNotSlashPrefixedIsEmittedWithSlashDelimiterWhenUriIsCastToString()
    {
        $new = $this->uri->withPath('foo/bar');

        $this->assertSame('https://user:pass@local.example.com:8042/foo/bar?name=ferret#nose', (string)$new);
    }

    public function testStripsQueryPrefixIfPresent()
    {
        $new = $this->uri->withQuery('?foo=bar');

        $this->assertSame('foo=bar', $new->getQuery());
    }

    public function testEncodeFragmentPrefixIfPresent()
    {
        $new = $this->uri->withFragment('#/foo/bar');

        $this->assertSame('%23/foo/bar', $new->getFragment());
    }

    public function standardSchemePortCombinations()
    {
        return [
            'http' => ['http', 80],
            'https' => ['https', 443],
        ];
    }

    /**
     * @dataProvider standardSchemePortCombinations
     * @param string $scheme Esquema a ser testado.
     * @param int|null $port Porta a ser testada.
     */
    public function testAuthorityOmitsPortForStandardSchemePortCombinations($scheme, $port)
    {
        $uri = $this->uri->withScheme($scheme)->withPort($port);

        $this->assertSame('user:pass@local.example.com', $uri->getAuthority());
    }

    public function testPathIsProperlyEncoded()
    {
        $uri = $this->uri->withPath('/foo^bar');
        $expected = '/foo%5Ebar';

        $this->assertSame($expected, $uri->getPath());
    }

    public function testPathDoesNotBecomeDoubleEncoded()
    {
        $uri = $this->uri->withPath('/foo%5Ebar');
        $expected = '/foo%5Ebar';

        $this->assertSame($expected, $uri->getPath());
    }

    public function queryStringsForEncoding()
    {
        return [
            'key-only' => ['k^ey', 'k%5Eey'],
            'key-value' => ['k^ey=valu`', 'k%5Eey=valu%60'],
            'array-key-only' => ['key[]', 'key%5B%5D'],
            'array-key-value' => ['key[]=valu`', 'key%5B%5D=valu%60'],
            'complex' => ['k^ey&key[]=valu`&f<>=`bar', 'k%5Eey&key%5B%5D=valu%60&f%3C%3E=%60bar'],
        ];
    }

    /**
     * @dataProvider queryStringsForEncoding
     * @param string $query Segmento de busca a ser testado.
     * @param string $expected Resultado esperado.
     */
    public function testQueryIsProperlyEncoded($query, $expected)
    {
        $uri = $this->uri->withQuery($query);

        $this->assertSame($expected, $uri->getQuery());
    }

    /**
     * @dataProvider queryStringsForEncoding
     * @param string $query Segmento de busca a ser testado.
     * @param string $expected Resultado esperado.
     */
    public function testQueryIsNotDoubleEncoded($query, $expected)
    {
        $uri = $this->uri->withQuery($expected);

        $this->assertSame($expected, $uri->getQuery());
    }

    public function testFragmentIsProperlyEncoded()
    {
        $uri = $this->uri->withFragment('/p^th?key^=`bar#b@z');
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';

        $this->assertSame($expected, $uri->getFragment());
    }

    public function testFragmentIsNotDoubleEncoded()
    {
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        $uri = $this->uri->withFragment($expected);

        $this->assertSame($expected, $uri->getFragment());
    }

    public function testProperlyTrimsLeadingSlashesToPreventXSS()
    {
        $url = 'http://example.org//woss.eng.br';
        $uri = new Uri($url);

        $this->assertSame('http://example.org/woss.eng.br', (string)$uri);
    }

    public function invalidStringComponentValues()
    {
        $methods = [
            'withScheme',
            'withUserInfo',
            'withHost',
            'withPath',
            'withQuery',
            'withFragment',
        ];

        $values = [
            'null' => null,
            'true' => true,
            'false' => false,
            'zero' => 0,
            'int' => 1,
            'zero-float' => 0.0,
            'float' => 1.1,
            'array' => ['value'],
            'object' => (object)['value' => 'value'],
        ];

        $combinations = [];

        foreach ($methods as $method) {
            foreach ($values as $type => $value) {
                $key = sprintf('%s-%s', $method, $type);
                $combinations[$key] = [$method, $value];
            }
        }

        return $combinations;
    }

    /**
     * @dataProvider invalidStringComponentValues
     * @param string $method Método a ser testado
     * @param mixed $value Parâmetro a ser testado.
     */
    public function testPassingInvalidValueToWithMethodReturnsNull($method, $value)
    {
        $new = $this->uri->$method($value);

        $this->assertNull($new);
    }

    public function testUtf8Uri()
    {
        $uri = new Uri('http://ουτοπία.δπθ.gr/');

        $this->assertSame('ουτοπία.δπθ.gr', $uri->getHost());
    }

    public function utf8PathsDataProvider()
    {
        return [
            ['http://example.com/тестовый_путь/', '/тестовый_путь/'],
            ['http://example.com/ουτοπία/', '/ουτοπία/']
        ];
    }

    /**
     * @dataProvider utf8PathsDataProvider
     * @param string $url URL a ser testada.
     * @param string $result Resultado esperado.
     */
    public function testUtf8Path($url, $result)
    {
        $uri = new Uri($url);

        $this->assertSame($result, $uri->getPath());
    }

    public function utf8QueryStringsDataProvider()
    {
        return [
            ['http://example.com/?q=тестовый_путь', 'q=тестовый_путь'],
            ['http://example.com/?q=ουτοπία', 'q=ουτοπία'],
        ];
    }

    /**
     * @dataProvider utf8QueryStringsDataProvider
     * @param string $url URL a ser testada.
     * @param string $result Resultado esperado.
     */
    public function testUtf8Query($url, $result)
    {
        $uri = new Uri($url);

        $this->assertSame($result, $uri->getQuery());
    }

    public function testUriDoesNotAppendColonToHostIfPortIsEmpty()
    {
        $uri = (new Uri('/'))->withHost('woss.eng.br');

        $this->assertSame('//woss.eng.br/', (string) $uri);
    }

    public function testReservedCharsInPathUnencoded()
    {
        $uri = $this->uri
            ->withScheme('https')
            ->withHost('api.linkedin.com')
            ->withPath('/v1/people/~:(first-name,last-name,email-address,picture-url)');

        $this->assertStringContainsString('/v1/people/~:(first-name,last-name,email-address,picture-url)', (string) $uri);
    }

    public function testHostIsLowercase()
    {
        $uri = new Uri('http://HOST.LOC/path?q=1');

        $this->assertSame('host.loc', $uri->getHost());
    }

    public function testHostIsLowercaseWhenIsSetByWithHost()
    {
        $uri = (new Uri('/'))->withHost('NEW-HOST.COM');

        $this->assertSame('new-host.com', $uri->getHost());
    }

    public function testUriDistinguishZeroFromEmptyString()
    {
        $expected = 'https://0:0@0:1/0?0#0';
        $uri = new Uri($expected);

        $this->assertSame($expected, (string) $uri);
    }
}