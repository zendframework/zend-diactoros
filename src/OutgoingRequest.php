<?php
namespace Phly\Http;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\OutgoingRequestInterface;
use Psr\Http\Message\StreamableInterface;

/**
 * HTTP Request encapsulation for client-side requests.
 */
class OutgoingRequest implements OutgoingRequestInterface
{
    use WritableMessageTrait, RequestTrait;

    /**
     * @param string $protocol
     * @param string|resource|StreamableInterface $stream
     */
    public function __construct($stream = 'php://memory')
    {
        if (! is_string($stream) && ! is_resource($stream) && ! $stream instanceof StreamableInterface) {
            throw new InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamableInterface implementation'
            );
        }

        if (! $stream instanceof StreamableInterface) {
            $stream = new Stream($stream, 'r');
        }

        $this->setBody($stream);
    }

    /**
     * Sets the method to be performed on the resource identified by the Request-URI.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * @param string $method Case-insensitive method.
     *
     * @return void
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Sets the request URL.
     *
     * @param string $url Request URL.
     *
     * @throws InvalidArgumentException If the URL is invalid.
     */
    public function setUrl($url)
    {
        if (! is_string($url)) {
            throw new InvalidArgumentException('Invalid URL provided; must be a string');
        }

        $uriInstance = new Uri($url);

        if (! $uriInstance->isValid()) {
            throw new InvalidArgumentException('Invalid URL provided');
        }

        $this->url = $url;
    }
}
