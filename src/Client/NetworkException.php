<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Woss\Http\Client;

use Exception;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

class NetworkException extends Exception implements NetworkExceptionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRequest(): RequestInterface
    {
        // TODO: Implement getRequest() method.
    }
}