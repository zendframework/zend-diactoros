<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Diactoros\Response;

use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Exception\InvalidArgumentException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

use function sprintf;

/**
 * Class DownloadResponse
 * @package Zend\Diactoros\Response
 */
class DownloadResponse extends Response
{
    use DownloadResponseTrait;

    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';
    const DEFAULT_DOWNLOAD_FILENAME = 'download';

    /**
     * DownloadResponse constructor.
     *
     * @param string|StreamInterface $body String or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param string $filename The file name to be sent with the response
     * @param string $contentType The content type to be sent with the response
     * @param array $headers An array of optional headers. These cannot override those set in getDownloadHeaders       */
    public function __construct(
        $body,
        int $status = 200,
        string $filename = self::DEFAULT_DOWNLOAD_FILENAME,
        string $contentType = self::DEFAULT_CONTENT_TYPE,
        array $headers = []
    ) {
        $this->filename = $filename;
        $this->contentType = $contentType;

        parent::__construct(
            $this->createBody($body),
            $status,
            $this->prepareDownloadHeaders($headers)
        );
    }

    /**
     * @param string|StreamInterface $content
     * @return StreamInterface
     * @throws InvalidArgumentException if $body is neither a string nor a Stream
     */
    private function createBody($content): StreamInterface
    {
        if ($content instanceof StreamInterface) {
            return $content;
        }

        if (!is_string($content)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid content (%s) provided to %s',
                (is_object($content) ? get_class($content) : gettype($content)),
                __CLASS__
            ));
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($content);
        $body->rewind();
        return $body;
    }
}
