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
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

abstract class Message implements MessageInterface
{
    /**
     * @var string Número da versão do protocolo HTTP
     */
    private $protocol = '1.1';

    /**
     * @var string[][] Conjunto de valores associados aos cabeçalhos HTTP
     */
    private $headers = [];

    /**
     * @var StreamInterface Corpo da mensagem HTTP
     */
    private $body;

    /**
     * Retorna a versão do protocolo HTTP como string.
     *
     * A string deve conter apenas o número da versão (ex. "1.1", "1.0").
     *
     * @return string Versão do protocolo HTTP
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * Retorna uma instância com a versão do protocolo HTTP especificado.
     *
     * A string deve conter apenas o número da versão (ex. "1.1", "1.0").
     *
     * @param string $version Versão do protocolo HTTP
     * @return MessageInterface Mensagem HTTP com a nova versão de protocolo
     */
    public function withProtocolVersion($version): MessageInterface
    {
        // TODO: Fazer a validação da versão do protocolo
        $new = clone $this;
        $new->protocol = $version;

        return $new;
    }

    /**
     * Retorna todos os cabeçalhos da mensagem.
     *
     * As chaves representam os nomes dos cabeçalhos da mensagem e cada valor é um array de strings associadas ao
     * cabeçalho.
     *
     * @return string[][] Retorna um array associativo com os cabeçalhos da mensagem.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Retorna os valores relacionados ao cabeçalho identificado pelo nome, separados por uma vírgula.
     *
     * Este método retorna todos os valores relacionados ao cabeçalho identificado pelo nome em uma string concatenada
     * separados por uma vírgula. O nome do cabeçalho é insensível à maiúsculas e minúsculas.
     *
     * NOTA: nem todos os cabeçalhos podem ser devidamente representados por valores separados por vírgula. Para
     * esses cabeçalhos recomenda-se o uso de getGeader() e implemente sua própria lógica de concatenação.
     *
     * Se o cabeçalho não existir na mensagem, uma string vazia será retornada.
     *
     * @param string $name Nome do cabeçalho a ser retornado
     * @return string Valores relacionados ao cabeçalho concatenados e separados por vírgula. String vazia se não
     *     existir.
     */
    public function getHeaderLine($name): string
    {
        if (!$this->hasHeader($name)) {
            return '';
        }

        return implode(', ', $this->getHeader($name));
    }

    /**
     * Verifica se um cabeçalho existe na mensagem identificado pelo seu nome.
     *
     * O nome do cabeçalho é insensível à maiúsculas e minúsculas, portanto, dois nomes que se diferenciam apenas na
     * caixa serão considerados o mesmo cabeçalho.
     *
     * @param string $name Nome do cabeçalho a ser verificado
     * @return bool Retorna verdadeiro se existir, falso caso contrário
     */
    public function hasHeader($name): bool
    {
        $name = $this->normalizeHeaderName($name);

        return array_key_exists($name, $this->headers);
    }

    /**
     * Retorna a forma normalizada do nome do cabeçalho.
     *
     * Como o nome do cabeçalho é insensível à maiúsculas e minúsculas, a forma normalizada, com a litra inicial de
     * cada palavra maiúscula e o resto minúscula, será utilizada para efeitos de comparação.
     *
     * @param string $name Nome do cabeçalho
     * @return string Forma normalizada do nome do cabeçalho
     */
    private function normalizeHeaderName($name): string
    {
        return implode('-', array_map('ucfirst', explode('-', $name)));
    }

    /**
     * Retorna os valores de um cabeçalho identificado pelo seu nome.
     *
     * Este método retorna um array com todos os valores relacionados ao cabeçalho. O nome do cabeçalho é insensível à
     * maiúsculas e minúsculas.
     *
     * Se o cabeçalho informado não existir, um array vazio será retornado.
     *
     * @param string $name Nome do cabeçalho a ser retornado
     * @return string[] Conjunto com todos os valores relacionados ao cabeçalho
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
     * Retorna uma instância com o valor adicionado ao cabeçalho da mensagem atual.
     *
     * Valores existentes para o cabeçalho informado serão mantidos na mensagem. O novo valor será apenas adicionado
     * ao cabeçalho; se esse ainda não existir na mensagem, será criado.
     *
     * @param string $name Nome do cabeçalho a ser adicionado
     * @param string|string[] Valor(es) a serem adicionados no cabeçalho
     * @return MessageInterface Mensagem HTTP com o novo valor do cabeçalho adicionado
     * @throws InvalidArgumentException Quando o nome ou valor forem inválidos
     */
    public function withAddedHeader($name, $value): MessageInterface
    {
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        // TODO: fazer a validação do nome do cabeçalho
        // TODO: fazer a validação dos valores do cabeçalho

        $name = $this->normalizeHeaderName($name);

        if (!is_array($value)) {
            $value = [$value];
        }

        $new = clone $this;
        $new->headers[$name] = array_merge($new->headers[$name], $value);

        return $new;
    }

    /**
     * Retorna uma instância substituindo o cabeçalho identificado pelo nome.
     *
     * @param string $name Nome do cabeçalho a ser substituído
     * @param string|string[] $value Valor(es) relacionado ao cabeçalho
     * @return MessageInterface Mensagem HTTP com os valores do cabeçalho substituídos
     * @throws InvalidArgumentException Quando o nome ou o valor do cabeçalho é inválido
     */
    public function withHeader($name, $value): MessageInterface
    {
        // TODO: fazer a validação do nome do cabeçalho
        $name = $this->normalizeHeaderName($name);
        $new = clone $this;
        // TODO: fazer a validação dos valores do cabeçalho
        $new->headers[$name] = $value;

        return $new;
    }

    /**
     * Returna uma instância sem o cabeçalho identificado pelo nome.
     *
     * O cabeçalho identificado pelo nome, insensível à maiúsculas e minúsculas, será removido da nova mensagem HTTP.
     * Todos os outros cabeçalhos permanecerão inalterados.
     *
     * @param string $name Nome do cabeçalho a ser removido na nova mensagem
     * @return MessageInterface Mensagem HTTP sem o cabeçalho
     */
    public function withoutHeader($name): MessageInterface
    {
        $new = clone $this;

        if ($new->hasHeader($name)) {
            $name = $this->normalizeHeaderName($name);
            unset($new->headers[$name]);
        }

        return $new;
    }

    /**
     * Retorna o corpo da mensagem HTTP.
     *
     * @return StreamInterface Corpo da mensagem HTTP
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * Retorna uma instância substituindo o corpo da mensagem HTTP.
     *
     * @param StreamInterface $body Corpo a ser utilizado pela nova mensagem HTTP
     * @return MessageInterface Mensagem HTTP com o novo corpo
     * @throws InvalidArgumentException Quando o novo corpo é inválido
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }
}