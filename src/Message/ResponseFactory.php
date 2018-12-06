<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Woss\Http\Message;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create a new response.
     *
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     *     in generated response; if none is provided implementations MAY use
     *     the defaults as suggested in the HTTP specification.
     *
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        // TODO: Implement createResponse() method.
    }
}