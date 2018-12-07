<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

declare(strict_types=1);

namespace Woss\Http\Message;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $uploadedFiles = [];

        return new ServerRequest(
            $serverParams,
            $uploadedFiles,
            $uri,
            $method,
            'php://temp'
        );
    }

    /**
     * @param array|null $server
     * @param array|null $query
     * @param array|null $body
     * @param array|null $cookies
     * @param array|null $files
     * @return ServerRequest
     */
    public static function fromGlobals(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ): ServerRequest
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

        return new ServerRequest(
            $server ?? $_SERVER,
            $files ?? $_FILES,
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD'],
            'php://input',
            $headers,
            $cookies ?: $_COOKIE,
            $query ?: $_GET,
            $body ?: $_POST,
            $_SERVER['SERVER_PROTOCOL']
        );
    }
}