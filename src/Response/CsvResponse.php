<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Diactoros\Response;

use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Exception;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

use function get_class;
use function gettype;
use function is_object;
use function is_string;
use function sprintf;

/**
 * CSV response.
 *
 * Allows creating a CSV response by passing a string to the constructor;
 * by default, sets a status code of 200 and sets the Content-Type header to
 * text/csv.
 */
class CsvResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Create a CSV response.
     *
     * Produces a CSV response with a Content-Type of text/csv and a default
     * status of 200.
     *
     * @param string|StreamInterface $text String or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param string $filename
     * @param array $headers Array of headers to use at initialization.
     */
    public function __construct($text, int $status = 200, string $filename = '', array $headers = [])
    {
        if (is_string($filename) && $filename !== '') {
            $headers = $this->prepareDownloadHeaders($filename, $headers);
        }

        parent::__construct(
            $this->createBody($text),
            $status,
            $this->injectContentType('text/csv; charset=utf-8', $headers)
        );
    }

    /**
     * Create the CSV message body.
     *
     * @param string|StreamInterface $text
     * @return StreamInterface
     * @throws Exception\InvalidArgumentException if $text is neither a string or stream.
     */
    private function createBody($text) : StreamInterface
    {
        if ($text instanceof StreamInterface) {
            return $text;
        }

        if (! is_string($text)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid CSV content (%s) provided to %s',
                (is_object($text) ? get_class($text) : gettype($text)),
                __CLASS__
            ));
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($text);
        $body->rewind();
        return $body;
    }
}
