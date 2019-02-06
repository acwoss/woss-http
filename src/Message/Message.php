<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

declare(strict_types=1);

namespace Woss\Http\Message;

abstract class Message
{
    /**
     * @var string
     */
    private $protocol = '1.1';

    /**
     * @var string[][]
     */
    private $headers = [];

    /**
     * @var Stream
     */
    private $body;

    /**
     * Inicializa uma nova instância de Message.
     *
     * @param string|resource|Stream $body Corpo da mensagem.
     * @param array $headers Lista de cabeçalhos.
     */
    protected function __construct($body = 'php://memory', $headers = [])
    {
        $this->setBody($body, 'wb+');
        $this->setHeaders($headers);
    }

    /**
     * Retorna a versão do protocolo HTTP.
     *
     * @return string Versão do protocolo HTTP.
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * Define a versão do protocolo HTTP.
     *
     * @param string $version Versão do protocolo HTTP.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setProtocolVersion($version): bool
    {
        if (!is_string($version)) {
            return false;
        }

        if (!preg_match('\d+\.\d+', $version)) {
            return false;
        }

        $this->protocol = $version;

        return true;
    }

    /**
     * Retorna uma cópia da mensagem com a nova versão do protocolo definida.
     *
     * @param string $version Versão do protocolo HTTP.
     * @return Message|null Cópia da mensagem com a nova versão do protocolo, nulo em caso de falha.
     */
    public function withProtocolVersion($version): ?Message
    {
        $new = clone $this;

        if (!$new->setProtocolVersion($version)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna a lista de cabeçalhos da mensagem.
     *
     * @return array Array associativo em que as chaves são os nomes dos cabeçalhos e os valores são os um array
     *      sequencial com os respectivos valores.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Define a lista de cabeçalhos da mensagem.
     *
     * @param array $headers Array associativo com os cabeçalhos da mensagem. A chave representa o nome do cabeçalho,
     *      enquanto o valor é um array com os respectivos valores.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setHeaders($headers): bool
    {
        if (!is_array($headers)) {
            return false;
        }

        foreach ($headers as $name => $value) {
            if (!$this->setHeader($name, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Define um cabeçalho da mensagem.
     *
     * @param string $name Nome do cabeçalho.
     * @param mixed $value Valor do cabeçalho.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setHeader($name, $value): bool
    {
        $name = $this->normalizeHeaderName($name);

        if (is_null($name)) {
            return false;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $v) {
            if (
                (!is_string($v) && !is_numeric($v)) ||
                preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $v) ||
                preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $v)
            ) {
                return false;
            }
        }

        $this->headers[$name] = $value;

        return true;
    }

    /**
     * Retorna os valores do cabeçalho separados por vírgula.
     *
     * @param string $name Nome do cabeçalho.
     * @return string Valores do cabeçalho separados por vírgula.
     */
    public function getHeaderLine($name): string
    {
        if (!$this->hasHeader($name)) {
            return '';
        }

        return implode(', ', $this->getHeader($name));
    }

    /**
     * Verifica se a mensagem possui determinado cabeçalho.
     *
     * @param string $name Nome do cabeçalho.
     * @return bool Verdadeiro se possuir, falso caso contrário.
     */
    public function hasHeader($name): bool
    {
        $name = $this->normalizeHeaderName($name);

        if (is_null($name)) {
            return false;
        }

        return array_key_exists($name, $this->headers);
    }

    /**
     * Retorna o array de valores de determinado cabeçalho.
     *
     * @param string $name Nome do cabeçalho.
     * @return array Lista de valores do cabeçalho.
     */
    public function getHeader($name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        $name = $this->normalizeHeaderName($name);

        return $this->headers[$name];
    }

    /**
     * Retorna uma cópia da mensagem adicionando um cabeçalho.
     *
     * @param string $name Nome do cabeçalho.
     * @param mixed $value Valor do cabeçalho.
     * @return Message|null Cópia da mensagem com o novo cabeçalho, nulo em caso de falha.
     */
    public function withAddedHeader($name, $value): ?Message
    {
        if (is_array($value)) {
            $value = [$value];
        }

        if ($this->hasHeader($name)) {
            $value = array_merge($this->getHeader($name), $value);
        }

        return $this->withHeader($name, $value);
    }

    /**
     * Retorna uma cópia da mensagem definindo um cabeçalho.
     *
     * @param string $name Nome do cabeçalho.
     * @param mixed $value Valor do cabeçalho.
     * @return Message|null Cópia da mensagem com o novo cabeçalho, nulo em caso de falha.
     */
    public function withHeader($name, $value): Message
    {
        $new = clone $this;

        if (!$new->setHeader($name, $value)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna uma cópia da mensagem removendo um cabeçalho.
     *
     * @param string $name Nome do cabeçalho.
     * @return Message Cópia da mensagem sem o cabeçalho.
     */
    public function withoutHeader($name): Message
    {
        $new = clone $this;

        if ($new->hasHeader($name)) {
            $name = $this->normalizeHeaderName($name);
            unset($new->headers[$name]);
        }

        return $new;
    }

    /**
     * Retorna o corpo da mensagem.
     *
     * @return Stream Corpo da mensagem.
     */
    public function getBody(): Stream
    {
        return $this->body;
    }

    /**
     * Define o corpo da mensagem HTTP.
     *
     * @param string|resource|Stream $body Corpo da mensagem.
     * @param string $mode Modo de operação do corpo da mensagem.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setBody($body, string $mode = 'r'): bool
    {
        if (!is_string($body) && !is_resource($body) && !($body instanceof Stream)) {
            return false;
        }

        if (is_string($body) || is_resource($body)) {
            $body = new Stream($body, $mode);
        }

        $this->body = $body;

        return true;
    }

    /**
     * Retorna uma cópia da mensagem definindo o novo corpo.
     *
     * @param string|resource|Stream $body Corpo da mensagem.
     * @return Message|null Cópia da mensagem com o novo corpo, nulo em caso de falha.
     */
    public function withBody($body): ?Message
    {
        $new = clone $this;

        if (!$new->setBody($body)) {
            return null;
        }

        return $new;
    }

    /**
     * Retorna o nome do cabeçalho normalizado.
     *
     * @param string $name Nome do cabeçalho.
     * @return string|null Nome normalizado do cabeçalho, nulo em caso de falha.
     */
    private function normalizeHeaderName($name): ?string
    {
        if (!is_string($name) || !preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            return null;
        }

        return implode('-', array_map('ucfirst', explode('-', $name)));
    }
}