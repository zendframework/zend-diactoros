<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Diactoros\Response;

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
     * DownloadResponse constructor.
     * @param $body
     * @param int $status
     * @param string $filename
     * @param array $headers
     */
    public function __construct($body, int $status = 200, string $filename = '', array $headers = [])
    {
        $content = new Stream('php://temp', 'wb+');
        $content->write($body);
        $content->rewind();

        $headers = $this->prepareDownloadHeaders($filename, $headers);

        parent::__construct($content, $status, $headers);
    }

    /**
     * Get download headers
     *
     * @param string $filename
     * @return array
     */
    private function getDownloadHeaders(string $filename): array
    {
        $headers = [];
        $headers['cache-control'] = ['must-revalidate'];
        $headers['content-description'] = ['File Transfer'];
        $headers['content-disposition'] = [sprintf('attachment; filename=%s', $filename)];
        $headers['content-transfer-encoding'] = ['Binary'];
        $headers['content-type'] = ['text/csv; charset=utf-8'];
        $headers['expires'] = ['0'];
        $headers['pragma'] = ['Public'];

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
     * @param string $filename
     * @param array $headers
     * @return array
     */
    private function prepareDownloadHeaders(string $filename, array $headers = []) : array
    {
        if ($this->overridesDownloadHeaders($this->downloadResponseHeaders, $headers)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot override download headers (%s) when download response is being sent',
                    implode(', ', $this->downloadResponseHeaders)
                )
            );
        }

        return array_merge($headers, $this->getDownloadHeaders($filename));
    }
}
