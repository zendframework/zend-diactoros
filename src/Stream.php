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
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * @return string
     */
    public function __toString()
    {
        if (! $this->isReadable()) {
            return '';
        }

        return stream_get_contents($this->resource, -1, 0);
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
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
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
     * Get the size of the stream if known
     *
     * @return int|null Returns the size in bytes if known, or null if unknown
     */
    public function getSize()
    {
        return null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int|bool Position of the file pointer or false on error
     */
    public function tell()
    {
        if (! $this->resource) {
            return false;
        }

        return ftell($this->resource);
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        if (! $this->resource) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * Returns whether or not the stream is seekable
     *
     * @return bool
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
     * Seek to a position in the stream
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *                    based on the seek offset. Valid values are identical
     *                    to the built-in PHP $whence values for `fseek()`.
     *                    SEEK_SET: Set position equal to offset bytes
     *                    SEEK_CUR: Set position to current location plus offset
     *                    SEEK_END: Set position to end-of-stream plus offset
     *
     * @return bool Returns TRUE on success or FALSE on failure
     * @link   http://www.php.net/manual/en/function.fseek.php
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
     * Returns whether or not the stream is writable
     *
     * @return bool
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
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     *
     * @return int|bool Returns the number of bytes written to the stream on
     *                  success or FALSE on failure.
     */
    public function write($string)
    {
        if (! $this->resource) {
            return false;
        }

        return fwrite($this->resource, $string);
    }

    /**
     * Returns whether or not the stream is readable
     *
     * @return bool
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
     * Read data from the stream
     *
     * @param int $length Read up to $length bytes from the object and return
     *                    them. Fewer than $length bytes may be returned if
     *                    underlying stream call returns fewer bytes.
     *
     * @return string     Returns the data read from the stream.
     */
    public function read($length)
    {
        if (! $this->resource || ! $this->isReadable()) {
            return '';
        }

        if ($this->eof()) {
            return '';
        }

        return fread($this->resource, $length);
    }

    /**
     * Returns the remaining contents in a string, up to maxlength bytes.
     *
     * @param int $maxLength The maximum bytes to read. Defaults to -1 (read
     *                       all the remaining buffer).
     * @return string
     */
    public function getContents($maxLength = -1)
    {
        if (! $this->isReadable()) {
            return '';
        }

        return stream_get_contents($this->resource, $maxLength);
    }
}
