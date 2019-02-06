<?php
/**
 * Este arquivo percente à biblioteca Woss\\Http.
 *
 * @author Anderson Carlos Woss <anderson@woss.eng.br>
 * @license https://github.com/acwoss/woss-http/blob/master/LICENSE MIT License
 */
declare(strict_types=1);

namespace Woss\Http\Message;

class UploadedFile
{
    /**
     * @var int|null
     */
    private $size;

    /**
     * @var int
     */
    private $error;

    /**
     * @var string|null
     */
    private $clientFilename;

    /**
     * @var string|null
     */
    private $clientMediaType;

    /**
     * @var bool
     */
    private $moved = false;

    /**
     * @var string|null
     */
    private $file = null;

    /**
     * @var Stream|null
     */
    private $stream = null;

    /**
     * Inicializa uma nova instância de UploadedFile.
     *
     * @param string|resource|Stream $streamOrFile Caminho, recurso ou Stream a ser utilizado.
     * @param int|null $size Tamanho do arquivo.
     * @param int $errorStatus Código de erro durante o upload do arquivo.
     * @param string|null $clientFilename Nome do arquivo.
     * @param string|null $clientMediaType Extensão do arquivo.
     */
    public function __construct(
        $streamOrFile,
        $size = null,
        $errorStatus = UPLOAD_ERR_OK,
        $clientFilename = null,
        $clientMediaType = null
    )
    {
        if (UPLOAD_ERR_OK === $errorStatus) {
            $this->setStream($streamOrFile);
        }

        $this->setSize($size);
        $this->setError($errorStatus);
        $this->setClientFilename($clientFilename);
        $this->setClientMediaType($clientMediaType);
    }

    /**
     * Retorna a Stream para o arquivo enviado.
     *
     * @return Stream|null Stream para o arquivo enviado, nulo em caso de falha.
     */
    public function getStream(): Stream
    {
        if (
            $this->error !== UPLOAD_ERR_OK
            || $this->isMoved()
            || $this->stream instanceof Stream
        ) {
            return null;
        }

        $this->stream = new Stream($this->file);
        return $this->stream;
    }

    /**
     * Define a Stream do arquivo enviado.
     *
     * @param string|resource|Stream $streamOrFile Caminho, recurso ou Stream do arquivo no servidor.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setStream($streamOrFile): bool
    {
        if (!is_string($streamOrFile) && !is_resource($streamOrFile) && !($streamOrFile instanceof Stream)) {
            return false;
        }

        $file = null;
        $stream = null;

        if (is_string($streamOrFile)) {
            $file = $streamOrFile;
        }

        if (is_string($streamOrFile) || is_resource($streamOrFile)) {
            $stream = new Stream($streamOrFile);
        }

        $this->stream = $stream;
        $this->file = $file;

        return true;
    }

    /**
     * Retorna se o arquivo já foi movido anteriormente.
     *
     * @return bool Verdadeiro caso o arquivo já foi movido, falso caso contrário.
     */
    public function isMoved(): bool
    {
        return $this->moved;
    }

    /**
     * Move o arquivo para o destino especificado.
     *
     * @param string $targetPath Caminho de destino do arquivo.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    public function moveTo($targetPath): bool
    {
        if (
            $this->isMoved()
            || (UPLOAD_ERR_OK !== $this->error)
            || !is_string($targetPath)
            || empty($targetPath)
        ) {
            return false;
        }

        $targetDirectory = dirname($targetPath);

        if (
            !is_dir($targetDirectory)
            || !is_writable($targetDirectory)
        ) {
            return false;
        }

        if (false === move_uploaded_file($this->file, $targetPath)) {
            return false;
        }

        $this->moved = true;

        return true;
    }

    /**
     * Retorna o tamanho do arquivo
     *
     * @return int|null Tamanho do arquivo, nulo em caso de falha.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Define o tamanho do arquivo.
     *
     * @param int|null $size Tamanho do arquivo. Quando nulo será definido a partir da Stream.
     * @return bool Verdadeiro em caso de sucesso, falso em caso contrpario.
     */
    protected function setSize($size): bool
    {
        if (!is_int($size) && !is_null($size)) {
            return false;
        }

        if (is_null($size)) {
            if (is_null($this->stream)) {
                return false;
            }

            $size = $this->stream->getSize();
        }

        $this->size = $size;

        return true;
    }

    /**
     * Retorna o código do erro durante o upload.
     *
     * @return int Código do erro.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Define o código de erro durante o upload do arquivo.
     *
     * @param int $error Código do erro.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setError($error): bool
    {
        if (!is_int($error)) {
            return false;
        }

        if ($error < 0 || $error > 8) {
            return false;
        }

        $this->error = $error;

        return true;
    }

    /**
     * Retorna o nome do arquivo no cliente.
     *
     * @return string|null Nome do arquivo no cliente, nulo em caso de falha.
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * Define o nome do arquivo no cliente.
     *
     * @param string|null $clientFilename Nome do arquivo no cliente.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setClientFilename($clientFilename): bool
    {
        if (!is_string($clientFilename) && !is_null($clientFilename)) {
            return false;
        }

        $this->clientFilename = $clientFilename;

        return true;
    }

    /**
     * Retorna o tipo do arquivo no cliente.
     *
     * @return string|null Tipo do arquivo no cliente, nulo em caso de falha.
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Define o tipo do arquivo no cliente.
     *
     * @param string|null $clientMediaType Tipo do arquivo no cliente.
     * @return bool Verdadeiro em caso de sucesso, falso caso contrário.
     */
    protected function setClientMediaType($clientMediaType): bool
    {
        if (!is_string($clientMediaType) && !is_null($clientMediaType)) {
            return false;
        }

        $this->clientMediaType = $clientMediaType;

        return true;
    }

    /**
     * Retorna uma lista de UploadedFiles a partir de um array.
     *
     * @param array $files Lista de arquivos em formato de array.
     * @return array Lista de UploadedFiles.
     */
    public static function createFromArray($files): array
    {
        $uploadedFiles = [];

        $create = function ($name, $type, $size, $tmpName, $error): UploadedFile
        {
            return new static($tmpName, $size, $error, $name, $type);
        };

        foreach($files as $key => $file) {
            $uploadedFiles[$key] = is_array($file['error']) ? array_map(
                $create,
                $file['name'],
                $file['type'],
                $file['size'],
                $file['tmp_name'],
                $file['error']
            ) : $create(
                $file['name'],
                $file['type'],
                $file['size'],
                $file['tmp_name'],
                $file['error']
            );
        }

        return $uploadedFiles;
    }
}