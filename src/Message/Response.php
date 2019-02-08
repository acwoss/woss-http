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

class Response extends Message
{
    /**
     * @var string
     */
    private $reasonPhrase;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var array
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
     * Inicializa uma nova instância de Response.
     *
     * @param string|resource|Stream $body Corpo da resposta.
     * @param int $status Código da resposta.
     * @param array $headers Lista de cabeçalhos da resposta.
     * @throws InvalidArgumentException Quando falha em inicializar a mensagem.
     * @throws InvalidArgumentException Quando o status da resposta é inválido.
     */
    public function __construct($body = 'php://memory', $status = 200, $headers = [])
    {
        parent::__construct($body, $headers);

        if (!$this->setStatus($status)) {
            throw new InvalidArgumentException(sprintf(
                "Status da resposta inválido: %s. Esperado inteiro entre 100 e 600",
                $status
            ));
        }
    }

    /**
     * Define um novo estado para a resposta.
     *
     * @param int $code Novo código do estado da resposta.
     * @param string $reasonPhrase Nova mensagem do estado da resposta.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setStatus($code, $reasonPhrase = ''): bool
    {
        if (!is_numeric($code) || is_float($code) || $code < 100 || $code > 599) {
            return false;
        }

        if (!is_string($reasonPhrase)) {
            return false;
        }

        if ($reasonPhrase === '' && isset($this->phrases[$code])) {
            $reasonPhrase = $this->phrases[$code];
        }

        $this->reasonPhrase = $reasonPhrase;
        $this->statusCode = (int) $code;

        return true;
    }

    /**
     * Retorna o código do estado da resposta.
     *
     * @return int Código do estado da resposta.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Retorna uma cópia da resposta definindo um novo estado.
     *
     * @param int $code Novo código do estado da resposta.
     * @param string $reasonPhrase Mensagem do estado da resposta.
     * @return Response|null Cópia da resposta com o novo estado, nulo em caso de falha.
     */
    public function withStatus($code, $reasonPhrase = ''): ?Response
    {
        $new = clone $this;

        if (!$new->setStatus($code, $reasonPhrase)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna a mensagem do estado da resposta.
     *
     * @return string Mensagem do estado da resposta.
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Retorna a linha inicial da mensagem HTTP.
     *
     * @return string Linha inicial da mensagem HTTP.
     */
    protected function getStatusLine(): string
    {
        return sprintf(
            "HTTP/%s %s %s",
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
    }
}