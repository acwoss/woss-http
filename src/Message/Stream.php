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
use RuntimeException;
use function array_key_exists;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function get_resource_type;
use function is_int;
use function is_resource;
use function is_string;
use function restore_error_handler;
use function set_error_handler;
use function stream_get_contents;
use function stream_get_meta_data;
use function strstr;
use const E_WARNING;
use const SEEK_SET;

class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $resource;

    /**
     * @var string|resource
     */
    private $stream;

    /**
     * @param string|resource $stream
     * @param string $mode Mode with which to open stream
     * @throws InvalidArgumentException
     */
    public function __construct($stream, string $mode = 'r')
    {
        $this->setStream($stream, $mode);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (!$this->resource) {
            return;
        }

        $resource = $this->detach();

        fclose($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        if (null === $this->resource) {
            return null;
        }

        $stats = fstat($this->resource);

        if ($stats !== false) {
            return $stats['size'];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if (!$this->resource) {
            throw new RuntimeException();
        }

        $result = ftell($this->resource);

        if (!is_int($result)) {
            throw new RuntimeException();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        if (!$this->resource) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        if (!$this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);

        return $meta['seekable'];
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->resource) {
            throw new RuntimeException();
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException();
        }

        $result = fseek($this->resource, $offset, $whence);

        if (0 !== $result) {
            throw new RuntimeException();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        if (!$this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (
            strstr($mode, 'x')
            || strstr($mode, 'w')
            || strstr($mode, 'c')
            || strstr($mode, 'a')
            || strstr($mode, '+')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        if (!$this->resource) {
            throw new RuntimeException();
        }

        if (!$this->isWritable()) {
            throw new RuntimeException();
        }

        $result = fwrite($this->resource, $string);

        if (false === $result) {
            throw new RuntimeException();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        if (!$this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        $mode = $meta['mode'];

        return (strstr($mode, 'r') || strstr($mode, '+'));
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        if (!$this->resource) {
            throw new RuntimeException();
        }

        if (!$this->isReadable()) {
            throw new RuntimeException();
        }

        $result = fread($this->resource, $length);

        if (false === $result) {
            throw new RuntimeException();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if (!$this->isReadable()) {
            throw new RuntimeException();
        }

        $result = stream_get_contents($this->resource);

        if (false === $result) {
            throw new RuntimeException();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if (null === $key) {
            return stream_get_meta_data($this->resource);
        }

        $metadata = stream_get_meta_data($this->resource);

        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
    }

    /**
     * @param string|resource $stream
     * @param string $mode
     * @return static
     * @throws InvalidArgumentException
     */
    protected function setStream($stream, string $mode = 'r')
    {
        $error = null;
        $resource = $stream;

        if (is_string($stream)) {
            set_error_handler(function ($e) use (&$error) {
                if ($e !== E_WARNING) {
                    return;
                }

                $error = $e;
            });

            $resource = fopen($stream, $mode);

            restore_error_handler();
        }

        if ($error) {
            throw new InvalidArgumentException();
        }

        if (!is_resource($resource) || 'stream' !== get_resource_type($resource)) {
            throw new InvalidArgumentException();
        }

        if ($stream !== $resource) {
            $this->stream = $stream;
        }

        $this->resource = $resource;

        return $this;
    }
}