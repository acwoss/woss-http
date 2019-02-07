<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */

declare(strict_types=1);

namespace Woss\Http\Message;

class Stream
{
    /**
     * @var resource|null
     */
    private $resource;

    /**
     * Inicializa uma nova instância de Stream.
     *
     * @param string|resource $stream Conteúdo ou arquivo para se gerar a Stream.
     * @param string $mode Modo que será aberto a Stream.
     * @throws \InvalidArgumentException Quando o tipo de $stream é inválido.
     */
    public function __construct($stream, $mode = 'r')
    {
        if (false === $this->setResource($stream, $mode)) {
            throw new \InvalidArgumentException(sprintf(
                "Tipo inválido: %s. Esperado string ou resource",
                is_object($stream) ? get_class($stream) : gettype($stream)
            ));
        }
    }

    /**
     * Fecha a Stream.
     *
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    public function close(): bool
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        $resource = $this->detach();

        fclose($resource);

        return true;
    }

    /**
     * Retorna o recurso da Stream após separá-lo.
     *
     * @return resource|null Recurso separado da Stream, nulo em caso de falha.
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    /**
     * Retorna o tamanho da Stream.
     *
     * @return int|null Tamanho da Stream, nulo em caso de falha.
     */
    public function getSize(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $stats = fstat($this->resource);

        if (false !== $stats) {
            return $stats['size'];
        }

        return null;
    }

    /**
     * Retorna a posição de leitura/gravação do ponteiro do arquivo.
     *
     * @return int|null Posição de leitura/gravação do ponteiro.
     */
    public function tell(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $result = ftell($this->resource);

        if (false === $result) {
            return null;
        }

        return $result;
    }

    /**
     * Verifica se o ponteiro atingiu o final do arquivo.
     *
     * @return bool Verdadeiro se o ponteiro atingiu o final do arquivo, falso caso contrário.
     */
    public function eof(): bool
    {
        if (!is_resource($this->resource)) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * Verifica se a Stream pode ser consultada.
     *
     * @return bool Verdadeiro se a Stream puder ser consultada, falso caso contrário.
     */
    public function isSeekable(): bool
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return $meta['seekable'];
    }

    /**
     * Procura (seeks) em um ponteiro de arquivo.
     *
     * @param int $offset Posição até onde deseja movimentar o ponteiro.
     * @param int $whence Configuração sobre a movimentação do ponteiro.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    public function seek($offset, $whence = SEEK_SET): bool
    {
        if (!is_int($offset) || !is_int($whence) || !$this->isSeekable()) {
            return false;
        }

        $result = fseek($this->resource, $offset, $whence);

        if (0 !== $result) {
            return false;
        }

        return true;
    }

    /**
     * Retorna o ponteiro do arquivo para o início.
     *
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    public function rewind(): bool
    {
        return $this->seek(0);
    }

    /**
     * Verifica se o recurso possui permissão de escrita.
     *
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    public function isWritable(): bool
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (
            false !== strstr($mode, 'x')
            || false !== strstr($mode, 'w')
            || false !== strstr($mode, 'c')
            || false !== strstr($mode, 'a')
            || false !== strstr($mode, '+')
        );
    }

    /**
     * Escreve o conteúdo no recurso na posição atual do ponteiro.
     *
     * @param string $string Conteúdo a ser escrito na Stream.
     * @return int|null Quantidade de bytes escrito, nulo em caso de falha.
     */
    public function write($string): ?int
    {
        if (!is_string($string) || !$this->isWritable()) {
            return null;
        }

        $result = fwrite($this->resource, $string);

        if (false === $result) {
            return null;
        }

        return $result;
    }

    /**
     * Verifica se o recurso possui permissão de leitura.
     *
     * @return bool Verdadeiro se o recurso puder ser lido, falso em caso contrário.
     */
    public function isReadable(): bool
    {
        if (!is_resource($this->resource)) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (false !== strstr($mode, 'r') || false !== strstr($mode, '+'));
    }

    /**
     * Leitura binary-safe de arquivo.
     *
     * @param int $length Número de bytes a serem lidos.
     * @return string|null Conteúdo lido, nulo em caso de falha.
     */
    public function read($length): ?string
    {
        if (!is_int($length) || !$this->isReadable()) {
            return null;
        }

        $result = fread($this->resource, $length);

        if (false === $result) {
            return null;
        }

        return $result;
    }

    /**
     * Retorna o conteúdo da Stream como string.
     *
     * @return string|null Conteúdo da Stream.
     */
    public function getContents(): ?string
    {
        if (!$this->isReadable()) {
            return null;
        }

        $result = stream_get_contents($this->resource);

        if (false === $result) {
            return null;
        }

        return $result;
    }

    /**
     * Retorna as informações sobre o recurso.
     *
     * @param string|null $key Nome do item a ser retornado. Se nulo, retornará todos.
     * @return mixed Informações desejadas sobre o recurso, nulo em caso de falha.
     */
    public function getMetadata($key = null)
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $metadata = stream_get_meta_data($this->resource);

        if (is_null($key)) {
            return $metadata;
        }

        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
    }

    /**
     * Define o recurso da Stream.
     *
     * @param string|resource $stream String ou recurso a ser definido na Stream.
     * @param string $mode Modo que será aberto a Stream.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setResource($stream, $mode = 'r'): bool
    {
        if (!is_string($stream) || !is_resource($stream) || !is_string($mode)) {
            return false;
        }

        if (is_string($stream)) {
            $stream = fopen($stream, $mode);

            if (false === $stream) {
                return false;
            }
        }

        $this->resource = $stream;

        return true;
    }

    /**
     * Retorna a representação em string da Stream.
     *
     * @return string|null Representação da Stream, nulo em caso de falha.
     */
    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        if ($this->isSeekable() && !$this->rewind()) {
            return '';
        }

        return $this->getContents();
    }
}