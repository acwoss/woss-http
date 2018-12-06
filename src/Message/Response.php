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
use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{
    /**
     * @var string Mensagem de estado da resposta HTTP
     */
    private $reasonPhrase;

    /**
     * @var int Código de estado da resposta HTTP
     */
    private $statusCode;

    /**
     * @var array Mapa com as mensagens de cada código de estado
     */
    private $phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    /**
     * Retorna o código de estado da resposta.
     *
     * O código de estado é um número inteiro de 3 dígitos.
     *
     * @return int Código do estado da resposta
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Retorna uma instância com o código de estado informado.
     *
     * Se nenhuma mensagem de estado for informada, será utilizada a mensagem padrão relacionada ao código de estado,
     * conforme definida na RFC 7231 ou IANA.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code Código de estado a ser utilizado
     * @param string $reasonPhrase Mensagem de estado a ser utilizada
     * @return static Resposta com o novo código de estado
     * @throws InvalidArgumentException Quando o código de estado é inválido
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        if (!is_numeric($code) || is_float($code) || $code < 100 || $code > 599
        ) {
            throw new InvalidArgumentException(sprintf(
                "Código de estado inválido {$code}; precisa ser um inteiro entre 100 e 599, inclusive"
            ));
        }

        if (!is_string($reasonPhrase)) {
            throw new InvalidArgumentException(sprintf(
                'Mensagem de estado não suportado; esperado uma string, recebido %s',
                is_object($reasonPhrase) ? get_class($reasonPhrase) : gettype($reasonPhrase)
            ));
        }

        if ($reasonPhrase === '' && isset($this->phrases[$code])) {
            $reasonPhrase = $this->phrases[$code];
        }

        $new = clone $this;
        $new->reasonPhrase = $reasonPhrase;
        $new->statusCode = (int)$code;

        return $new;
    }

    /**
     * Retorna a mensagem de estado da resposta HTTP.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Mensagem de estado da resposta
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
}