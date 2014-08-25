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
 *
 * @property string $originalUrl Original URI for the request
 */
class Request extends AbstractMessage implements RequestInterface
{
    /**
     * @var string
     */
    private $method;

    /**
     * @var Uri
     */
    private $url;

    /**
     * User-set parameters (usually by middleware)
     *
     * @var array
     */
    private $userParams = array();

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
     * Retrieve arbitrary user parameters
     *
     * @param string $name
     * @return null|mixed null if $name does not exist
     */
    public function __get($name)
    {
        if (! array_key_exists($name, $this->userParams)) {
            return null;
        }

        return $this->userParams[$name];
    }

    /**
     * Set arbitrary user properties
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->userParams[$name] = $value;
    }

    /**
     * Test if a user property exists
     *
     * @param mixed $name
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->userParams);
    }

    /**
     * Remove a previously set user property
     *
     * @param string $name
     */
    public function __unset($name)
    {
        if (! array_key_exists($name, $this->userParams)) {
            return;
        }

        unset($this->userParams[$name]);
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

        if ($this->originalUrl === null) {
            $this->originalUrl = $url;
        }
        $this->url = $url;
    }
}
