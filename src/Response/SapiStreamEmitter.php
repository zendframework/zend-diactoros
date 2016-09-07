<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Response;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Zend\Diactoros\RelativeStream;

class SapiStreamEmitter implements EmitterInterface
{
    use SapiEmitterTrait;

    /**
     * Emits a response for a PHP SAPI environment.
     *
     * Emits the status line and headers via the header() function, and the
     * body content via the output buffer.
     *
     * @param ResponseInterface $response
     * @param int $maxBufferLength Maximum output buffering size for each iteration
     */
    public function emit(ResponseInterface $response, $maxBufferLength = 8192)
    {
        if (headers_sent()) {
            throw new RuntimeException('Unable to emit response; headers already sent');
        }

        $response = $this->injectContentLength($response);

        $this->emitStatusLine($response);
        $this->emitHeaders($response);
        $this->flush();

        $range = $this->parseContentRange($response->getHeaderLine('Content-Range'));

        if (is_array($range)) {
            $this->emitBodyRange($range, $response, $maxBufferLength);
            return;
        }

        $this->emitBody($response, $maxBufferLength);
    }

    /**
     * Emit the message body.
     *
     * @param ResponseInterface $response
     * @param int $maxBufferLength
     */
    private function emitBody(ResponseInterface $response, $maxBufferLength)
    {
        $body = $response->getBody();

        if (! $body->isSeekable()) {
            echo $body;
            return;
        }

        $body->rewind();
        while (! $body->eof()) {
            echo $body->read($maxBufferLength);
        }
    }

    /**
     * Emit a range of the message body.
     *
     * @param array $range
     * @param ResponseInterface $response
     * @param int $maxBufferLength
     */
    private function emitBodyRange(array $range, ResponseInterface $response, $maxBufferLength)
    {
        list($unit, $first, $last, $length) = $range;

        $body = $response->getBody();

        if (! $body->isSeekable()) {
            $contents = $body->getContents();
            echo substr($contents, $first, $last - $first + 1);
            return;
        }

        $body = new RelativeStream($body, $first);
        $body->rewind();
        $pos = 0;
        $length = $last - $first + 1;
        while (! $body->eof() && $pos < $length) {
            if (($pos + $maxBufferLength) > $length) {
                echo $body->read($length - $pos);
                break;
            }

            echo $body->read($maxBufferLength);
            $pos = $body->tell();
        }
    }

    /**
     * Parse content-range header
     * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.16
     *
     * @param string $header
     * @return false|array [unit, first, last, length]; returns false if no
     *     content range or an invalid content range is provided
     */
    private function parseContentRange($header)
    {
        if (preg_match('/(?P<unit>[\w]+)\s+(?P<first>\d+)-(?P<last>\d+)\/(?P<length>\d+|\*)/', $header, $matches)) {
            return [
                $matches['unit'],
                (int) $matches['first'],
                (int) $matches['last'],
                $matches['length'] === '*' ? '*' : (int) $matches['length'],
            ];
        }
        return false;
    }
}
