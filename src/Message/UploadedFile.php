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
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use function dirname;
use function fclose;
use function fopen;
use function fwrite;
use function is_dir;
use function is_resource;
use function is_string;
use function is_writable;
use function move_uploaded_file;
use function strpos;
use const PHP_SAPI;
use const UPLOAD_ERR_OK;

class UploadedFile implements UploadedFileInterface
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
    private $moved;

    /**
     * @var string|null
     */
    private $file;

    /**
     * @var StreamInterface|null
     */
    private $stream;

    /**
     * UploadedFile constructor.
     * @param $streamOrFile
     * @param int $size
     * @param int $errorStatus
     * @param string|null $clientFilename
     * @param string|null $clientMediaType
     */
    public function __construct(
        $streamOrFile,
        int $size,
        int $errorStatus,
        string $clientFilename = null,
        string $clientMediaType = null
    )
    {
        if ($errorStatus === UPLOAD_ERR_OK) {
            if (is_string($streamOrFile)) {
                $this->file = $streamOrFile;
            }

            if (is_resource($streamOrFile)) {
                $this->stream = new Stream($streamOrFile);
            }

            if (!$this->file && !$this->stream) {
                if (!$streamOrFile instanceof StreamInterface) {
                    throw new InvalidArgumentException();
                }
                $this->stream = $streamOrFile;
            }
        }

        $this->setSize($size);

        if (0 > $errorStatus || 8 < $errorStatus) {
            throw new InvalidArgumentException();
        }

        $this->setError($errorStatus);
        $this->setClientFilename($clientFilename);
        $this->setClientMediaType($clientMediaType);
    }

    /**
     * {@inheritdoc}
     */
    public function getStream(): StreamInterface
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException();
        }

        if ($this->moved) {
            throw new RuntimeException();
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream($this->file);
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    public function moveTo($targetPath)
    {
        if ($this->moved) {
            throw new RuntimeException();
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException();
        }

        if (!is_string($targetPath) || empty($targetPath)) {
            throw new InvalidArgumentException();
        }

        $targetDirectory = dirname($targetPath);

        if (!is_dir($targetDirectory) || !is_writable($targetDirectory)) {
            throw new InvalidArgumentException();
        }

        $sapi = PHP_SAPI;

        if (empty($sapi) || 0 === strpos($sapi, 'cli') || !$this->file) {
            $this->writeFile($targetPath);
        } else {
            if (false === move_uploaded_file($this->file, $targetPath)) {
                throw new RuntimeException();
            }
        }

        $this->moved = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * {@inheritdoc}
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Escreve a stream interna no arquivo especificado.
     *
     * @param string $path
     */
    private function writeFile(string $path): void
    {
        $handle = fopen($path, 'wb+');

        if (false === $handle) {
            throw new RuntimeException();
        }

        $stream = $this->getStream();
        $stream->rewind();

        while (!$stream->eof()) {
            fwrite($handle, $stream->read(4096));
        }

        fclose($handle);
    }

    /**
     * @param int|null $size
     * @return static
     */
    protected function setSize(?int $size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @param int $error
     * @return static
     */
    protected function setError(int $error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @param string|null $clientFilename
     * @return static
     */
    protected function setClientFilename(?string $clientFilename)
    {
        $this->clientFilename = $clientFilename;

        return $this;
    }

    /**
     * @param string|null $clientMediaType
     * @return static
     */
    protected function setClientMediaType(?string $clientMediaType)
    {
        $this->clientMediaType = $clientMediaType;

        return $this;
    }

    /**
     * @param StreamInterface|null $stream
     * @return static
     */
    protected function setStream(?StreamInterface $stream)
    {
        $this->stream = $stream;

        return $this;
    }
}