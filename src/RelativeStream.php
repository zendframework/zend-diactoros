<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

use Psr\Http\Message\StreamInterface;

class RelativeStream implements StreamInterface
{
    /**
     * @var StreamInterface
     */
    private $decodatedStream;

    /**
     * @var int
     */
    private $offset;

    /**
     * Class constructor
     *
     * @param StreamInterface $decodatedStream
     * @param $offset
     */
    public function __construct(StreamInterface $decodatedStream, $offset)
    {
        $this->decodatedStream = $decodatedStream;
        $this->offset = $offset;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $this->seek(0);
        return $this->getContents();
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
        $this->decodatedStream->close();
    }

    /**
     * @inheritdoc
     */
    public function detach()
    {
        return $this->decodatedStream->detach();
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return $this->decodatedStream->getSize() - $this->offset;
    }

    /**
     * @inheritdoc
     */
    public function tell()
    {
        return $this->decodatedStream->tell() - $this->offset;
    }

    /**
     * @inheritdoc
     */
    public function eof()
    {
        return $this->decodatedStream->eof();
    }

    /**
     * @inheritdoc
     */
    public function isSeekable()
    {
        return $this->decodatedStream->isSeekable();
    }

    /**
     * @inheritdoc
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        switch ($whence) {
            case SEEK_SET:
                $basePos = $this->offset;
                break;
            default:
                $basePos = 0;
        }
        return $this->decodatedStream->seek($offset + $basePos, $whence);
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        return $this->seek(0);
    }

    /**
     * @inheritdoc
     */
    public function isWritable()
    {
        return $this->decodatedStream->isWritable();
    }

    /**
     * @inheritdoc
     */
    public function write($string)
    {
        return $this->decodatedStream->write($string);
    }

    /**
     * @inheritdoc
     */
    public function isReadable()
    {
        return $this->decodatedStream->isReadable();
    }

    /**
     * @inheritdoc
     */
    public function read($length)
    {
        return $this->decodatedStream->read($length);
    }

    /**
     * @inheritdoc
     */
    public function getContents()
    {
        return $this->decodatedStream->getContents();
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($key = null)
    {
        return $this->decodatedStream->getMetadata($key);
    }
}
