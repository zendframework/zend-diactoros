<?php
namespace Phly\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

/**
 * Implementation of PSR HTTP streams
 */
class Stream implements StreamInterface
{
    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var string|resource
     */
    protected $stream;

    /**
     * @param string|resource $stream
     * @param string $mode Mode with which to open stream
     * @throws InvalidArgumentException
     */
    public function __construct($stream, $mode = 'r')
    {
        $this->stream = $stream;

        if (is_resource($stream)) {
            $this->resource = $stream;
        } elseif (is_string($stream)) {
            $this->resource = fopen($stream, $mode);
        } else {
            throw new InvalidArgumentException(
                'Invalid stream provided; must be a string stream identifier or resource'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        if (! $this->isReadable()) {
            return '';
        }

        return stream_get_contents($this->resource, -1, 0);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (! $this->resource) {
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
    public function attach($resource, $mode = 'r')
    {
        $error = null;
        if (! is_resource($resource) && is_string($resource)) {
            set_error_handler(function ($e) use (&$error) {
                $error = $e;
            }, E_WARNING);
            $resource = fopen($resource, $mode);
            restore_error_handler();
        }

        if ($error) {
            throw new InvalidArgumentException('Invalid stream reference provided');
        }

        if (! is_resource($resource)) {
            throw new InvalidArgumentException(
                'Invalid stream provided; must be a string stream identifier or resource'
            );
        }

        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (null === $this->resource) {
            return null;
        }

        $stats = fstat($this->resource);
        return $stats['size'];
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if (! $this->resource) {
            return false;
        }

        return ftell($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        if (! $this->resource) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        if (! $this->resource) {
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
        if (! $this->resource || ! $this->isSeekable()) {
            return false;
        }

        $result = fseek($this->resource, $offset, $whence);
        return (0 === $result);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if (! $this->isSeekable()) {
            return false;
        }

        $result = fseek($this->resource, 0);
        return (0 === $result);
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        if (! $this->resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->resource);
        return is_writable($meta['uri']);
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        if (! $this->resource) {
            return false;
        }

        return fwrite($this->resource, $string);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        if (! $this->resource) {
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
        if (! $this->resource || ! $this->isReadable()) {
            return false;
        }

        if ($this->eof()) {
            return '';
        }

        return fread($this->resource, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if (! $this->isReadable()) {
            return '';
        }

        return stream_get_contents($this->resource);
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
        if (! array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
    }
}
