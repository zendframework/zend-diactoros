<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Diactoros\Response;

use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Exception\InvalidArgumentException;
use Zend\Diactoros\Response;

use Zend\Diactoros\Stream;
use function array_keys;
use function array_merge;
use function implode;
use function in_array;
use function sprintf;

/**
 * Class DownloadResponse
 * @package Zend\Diactoros\Response
 */
class DownloadResponse extends Response
{
    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';
    const DEFAULT_DOWNLOAD_FILENAME = 'download';

    /**
     * A list of header keys required to be sent with a download response
     *
     * @var array
     */
    private $downloadResponseHeaders = [
        'cache-control',
        'content-description',
        'content-disposition',
        'content-transfer-encoding',
        'expires',
        'pragma'
    ];

    /**
     * @var string The filename to be sent with the response
     */
    private $filename;

    /**
     * @var string The content type to be sent with the response
     */
    private $contentType;

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
     * Get download headers
     *
     * @return array
     */
    private function getDownloadHeaders(): array
    {
        $headers = [];
        $headers['cache-control'] = 'must-revalidate';
        $headers['content-description'] = 'File Transfer';
        $headers['content-disposition'] = sprintf('attachment; filename=%s', self::DEFAULT_DOWNLOAD_FILENAME);
        $headers['content-transfer-encoding'] = 'Binary';
        $headers['content-type'] = 'application/octet-stream';
        $headers['expires'] = '0';
        $headers['pragma'] = 'Public';

        return $headers;
    }

    /**
     * Check if the extra headers contain any of the download headers
     *
     * The download headers cannot be overridden.
     *
     * @param array $downloadHeaders
     * @param array $headers
     * @return bool
     */
    public function overridesDownloadHeaders(array $downloadHeaders, array $headers = []) : bool
    {
        $overridesDownloadHeaders = false;

        foreach (array_keys($headers) as $header) {
            if (in_array($header, $downloadHeaders)) {
                $overridesDownloadHeaders = true;
                break;
            }
        }

        return $overridesDownloadHeaders;
    }

    /**
     * Prepare download response headers
     *
     * This function prepares the download response headers. It does so by:
     * - Merging the optional with over the default ones (the default ones cannot be overridden)
     * - Set the content-type and content-disposition headers from $filename and $contentType passed
     *   to the constructor.
     *
     * @param array $headers
     * @return array
     * @throws InvalidArgumentException if an attempt is made to override a default header
     */
    private function prepareDownloadHeaders(array $headers = []) : array
    {
        if ($this->overridesDownloadHeaders($this->downloadResponseHeaders, $headers)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot override download headers (%s) when download response is being sent',
                    implode(', ', $this->downloadResponseHeaders)
                )
            );
        }

        return array_merge(
            $headers,
            $this->getDownloadHeaders(),
            [
                'content-disposition' => sprintf('attachment; filename=%s', $this->filename),
                'content-type' => $this->contentType,
            ]
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
