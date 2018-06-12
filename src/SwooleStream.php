<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use swoole_http_request;

class SwooleStream implements StreamInterface
{
    /**
     * @var string
     */
    protected $body;

    /**
     * @var int
     */
    protected $bodySize;

    /**
     * @var int
     */
    protected $index = 0;

    public function __construct(swoole_http_request $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if (! isset($this->body)) {
            $this->body = $this->request->rawcontent();
        }
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        if (! isset($this->bodySize)) {
            $this->bodySize = strlen($this->getContents());
        }
        return $this->bodySize;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        return $this->index;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return $this->index >= $this->getSize() - 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        $result = substr($this->getContents(), $this->index, $length);
        $this->index += $length;
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {

        switch ($whence) {
            case SEEK_SET:
                if ($offset >= $this->getSize()) {
                    throw new RuntimeException(
                        'Offset cannot be longer than content size'
                    );
                }
                $this->index = $offset;
                break;
            case SEEK_CUR:
                if ($offset + $this->index >= $this->getSize()) {
                    throw new RuntimeException(
                        'Offset + current position cannot be longer than content size'
                    );
                }
                $this->index += $offset;
                break;
            case SEEK_END:
                if ($offset + $this->getSize() >= $this->getSize()) {
                    throw new RuntimeException(
                        'Offset must be a negative number to be under the content size'
                    );
                }
                $this->index = $this->getSize() - 1 + $offset;
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        throw new RuntimeException('Stream is not writable');
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return null;
    }
}
