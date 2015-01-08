<?php
namespace Phly\Http;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamableInterface;

/**
 * HTTP Request encapsulation
 */
class Request implements RequestInterface
{
    use MessageTrait;

    /**
     * @var string
     */
    private $absoluteUri;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $url;

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
        $this->method = $method;
    }

    /**
     * Retrieves the absolute URI.
     *
     * An absolute URI consists of minimally scheme and host, but can also
     * contain:
     *
     * - authentication (user/pass) if provided
     * - port (if non-standard)
     * - path (if any)
     * - query string (if present)
     * - fragment (if present)
     *
     * If either of the scheme or host are not present, this method MUST return
     * null.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return string|null Returns the absolute URL as a string. The URL MUST
     *     include the scheme and host; if the port is non-standard for the
     *     scheme, the port MUST be included; authentication data MAY be
     *     provided. If either host or scheme are missing, this method MUST
     *     return null.
     */
    public function getAbsoluteUri()
    {
        return $this->absoluteUri;
    }

    /**
     * Sets the absolute URI of the request.
     *
     * The absolute URI MUST be a string, and MUST include the scheme and host.
     *
     * If the port is non-standard for the scheme, the port MUST be provided.
     *
     * Authentication data MAY be provided.
     *
     * Path, query string, and fragment are optional.
     *
     * When setting the absolute URI, the url (see getUrl() and setUrl()) MUST
     * be updated.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param string $uri Absolute request URI.
     * @return void
     * @throws InvalidArgumentException If the URI is invalid.
     */
    public function setAbsoluteUri($uri)
    {
        if (! is_string($uri)) {
            throw new InvalidArgumentException('Invalid URL provided; must be a string');
        }

        $parts = parse_url($uri);
        if (! isset($parts['scheme'])
            || empty($parts['scheme'])
            || ! isset($parts['host'])
            || empty($parts['host'])
        ) {
            throw new InvalidArgumentException(
                'The request absolute URI MUST contain both a scheme and host'
            );
        }

        $this->absoluteUri = $uri;
        $this->setUrlFromAbsoluteUri($parts);
    }

    /**
     * Retrieves the request URL.
     *
     * The request URL is the path and query string ONLY.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3
     * @return string Returns the URL as a string. The URL MUST be an
     *     origin-form (path + query string), per RFC 7230 section 5.3
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Sets the request URL.
     *
     * The URL MUST be a string. The URL SHOULD be an origin-form (path + query
     * string) per RFC 7230 section 5.3; if other URL parts are present, the
     * method MUST raise an exception OR remove those parts.
     *
     * When setting the URL, the absolute URI (see getAbsoluteUri() and
     * setAbsoluteUri()) MUST be updated.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3
     * @param string $url Request URL, with path and optionally query string.
     * @return void
     * @throws \InvalidArgumentException If the URL is invalid.
     */
    public function setUrl($url)
    {
        if (! is_string($url)) {
            throw new InvalidArgumentException('Invalid URL provided; must be a string');
        }

        $parts      = parse_url($url);
        $normalized = '';

        if (isset($parts['path']) && ! empty($parts['path'])) {
            $normalized .= $parts['path'];
        }

        if (isset($parts['query']) && ! empty($parts['query'])) {
            $normalized .= '?' . $parts['query'];
        }

        $this->url = $normalized;
        $this->setAbsoluteUriFromUrl($this->url);
    }

    /**
     * Set the URL from the parts present in the absolute URI
     *
     * @param array $parts
     * @return void
     */
    private function setUrlFromAbsoluteUri(array $parts)
    {
        $url = '';
        if (isset($parts['path']) && ! empty($parts['path'])) {
            $url .= $parts['path'];
        }

        if (isset($parts['query']) && ! empty($parts['query'])) {
            $url .= '?' . $parts['query'];
        }

        $this->url = $url;
    }

    /**
     * Helper function to update absolute URI from URL passed to setUrl()
     *
     * @param string $url
     * @return void
     */
    private function setAbsoluteUriFromUrl($url)
    {
        if (! $this->absoluteUri) {
            return;
        }

        $url   = '/' . ltrim($url, '/');
        $path  = parse_url($this->absoluteUri, PHP_URL_PATH);
        $query = parse_url($this->absoluteUri, PHP_URL_QUERY);

        if (null === $path && null === $query) {
            $this->setAbsoluteUri($this->absoluteUri . $url);
            return;
        }

        if (null === $path) {
            $baseUri = str_replace('?' . $query, '', $this->absoluteUri);
            $this->setAbsoluteUri($baseUri . $url);
            return;
        }

        if (null === $query) {
            $baseUri = str_replace($path, '', $this->absoluteUri);
            $this->setAbsoluteUri($baseUri . $url);
            return;
        }

        $baseUri = str_replace('?' . $query, '', $this->absoluteUri);
        $baseUri = str_replace($path, '', $baseUri);
        $this->setAbsoluteUri($baseUri . $url);
    }
}
