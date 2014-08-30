<?php
namespace Phly\Http;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP Request encapsulation
 *
 * Allows arbitrary properties, which allows it to be used to transfer
 * state between middlewares.
 */
class Request implements RequestInterface
{
    use MessageTrait;

    /**
     * @var string
     */
    private $method;

    /**
     * @var Uri
     */
    private $url;

    /**
     * @param string $protocol
     * @param string|resource|StreamInterface $stream
     */
    public function __construct($protocol = '1.1', $stream = 'php://input')
    {
        $this->protocol = $protocol;

        if (! is_string($stream) && ! is_resource($stream) && ! $stream instanceof StreamInterface) {
            throw new InvalidArgumentException('Stream must be a string stream resource identifier, an actual stream resource, or a Psr\Http\Message\StreamInterface implementation');
        }

        if (! $stream instanceof StreamInterface) {
            $stream = new Stream($stream, 'r');
        }

        $this->setBody($stream);
    }

    /**
     * Gets the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
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
        if ($this->method !== null) {
            throw new RuntimeException('Method cannot be overwritten');
        }
        $this->method = $method;
    }

    /**
     * Gets the absolute request URL.
     *
     * @return Uri
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the request URL.
     *
     * @param string|Uri $url Request URL.
     *
     * @throws InvalidArgumentException If the URL is invalid.
     */
    public function setUrl($url)
    {
        if (is_string($url)) {
            $url = new Uri($url);
        }

        if (! $url instanceof Uri) {
            throw new InvalidArgumentException('Invalid URL provided; must be a string or Uri instance');
        }

        if (! $url->isValid()) {
            throw new InvalidArgumentException('Invalid URL provided');
        }

        $this->url = $url;
    }
}
