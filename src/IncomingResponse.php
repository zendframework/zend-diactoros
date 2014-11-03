<?php
namespace Phly\Http;

use InvalidArgumentException;
use Psr\Http\Message\IncomingResponseInterface;
use Psr\Http\Message\StreamableInterface;

/**
 * HTTP response encapsulation.
 */
class IncomingResponse implements IncomingResponseInterface
{
    use MessageTrait, ResponseTrait, ImmutableHeadersTrait;

    /**
     * @param int $statusCode HTTP status code.
     * @param array $headers HTTP headers for the response.
     * @param string|resource|StreamableInterface $stream Stream identifier and/or actual stream resource
     * @param null|string $reasonPhrase HTTP status reason phrase, if any.
     */
    public function __construct($statusCode, array $headers, $stream, $reasonPhrase = null)
    {
        $this->setStatusCode($statusCode);
        $this->setHeaders($headers);
        $this->setBody($stream);
        $this->reasonPhrase = $reasonPhrase;
    }

    /**
     * Set the response status code.
     * 
     * @param int $statusCode 
     * @return void
     * @throws InvalidArgumentException for invalid status code type or out-of-range status code.
     */
    private function setStatusCode($statusCode)
    {
        if (! is_numeric($statusCode)
            || is_float($statusCode)
            || $statusCode < 100
            || $statusCode >= 600
        ) {
            throw new InvalidArgumentException(sprintf(
                'Invalid status code "%s"',
                $statusCode
            ));
        }

        $this->statusCode = (int) $statusCode;
    }

    /**
     * Set the content body of the response.
     * 
     * @param string|resource|StreamableInterface $stream 
     * @return void
     * @throws InvalidArgumentException for invalid streams
     */
    private function setBody($stream)
    {
        if (! is_string($stream) && ! is_resource($stream) && ! $stream instanceof StreamableInterface) {
            throw new InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamableInterface implementation'
            );
        }

        if (! $stream instanceof StreamableInterface) {
            $stream = new Stream($stream, 'wb+');
        }

        $this->stream = $stream;
    }
}
