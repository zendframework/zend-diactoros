<?php
namespace Phly\Http;

use Psr\Http\Message\RequestInterface;

/**
 * HTTP Request encapsulation
 *
 * Requests are considered immutable; all methods that might change state are
 * implemented such that they retain the internal state of the current
 * message and return a new instance that contains the changed state.
 */
class Request implements RequestInterface
{
    use MessageTrait, RequestTrait;

    /**
     * @param null|string $uri URI for the request, if any.
     * @param null|string $method HTTP method for the request, if any.
     * @param string|resource|StreamableInterface $body Message body, if any.
     * @param array $headers Headers for the message, if any.
     * @throws InvalidArgumentException for any invalid value.
     */
    public function __construct($uri = null, $method = null, $body = 'php://memory', array $headers = [])
    {
        $this->initialize($uri, $method, $body, $headers);
    }

    /**
     * Extends MessageInterface::getHeaders() to provide request-specific
     * behavior.
     *
     * Retrieves all message headers.
     *
     * This method acts exactly like MessageInterface::getHeaders(), with one
     * behavioral change: if the Host header has not been previously set, the
     * method MUST attempt to pull the host segment of the composed URI, if
     * present.
     *
     * @see MessageInterface::getHeaders()
     * @see UriInterface::getHost()
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings.
     */
    public function getHeaders()
    {
        $headers = $this->headers;
        if (! $this->hasHeader('host')
            && ($this->uri && $this->uri->getHost())
        ) {
            $headers['Host'] = [$this->getHostFromUri()];
        }

        return $headers;
    }

    /**
     * Extends MessageInterface::getHeaderLines() to provide request-specific
     * behavior.
     *
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * This method acts exactly like MessageInterface::getHeaderLines(), with
     * one behavioral change: if the Host header is requested, but has
     * not been previously set, the method MUST attempt to pull the host
     * segment of the composed URI, if present.
     *
     * @see MessageInterface::getHeaderLines()
     * @see UriInterface::getHost()
     * @param string $name Case-insensitive header field name.
     * @return string[]
     */
    public function getHeaderLines($header)
    {
        if (! $this->hasHeader($header)) {
            if (strtolower($header) === 'host'
                && ($this->uri && $this->uri->getHost())
            ) {
                return [$this->getHostFromUri()];
            }

            return [];
        }

        $header = $this->headerNames[strtolower($header)];

        $value = $this->headers[$header];
        $value = is_array($value) ? $value : [$value];
        return $value;
    }
}
