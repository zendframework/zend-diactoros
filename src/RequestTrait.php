<?php
namespace Phly\Http;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\StreamableInterface;
use Psr\Http\Message\UriInterface;

/**
 * Trait with common request behaviors.
 *
 * Server and client-side requests differ slightly in how the Host header is
 * handled; on client-side, it should be calculated on-the-fly from the
 * composed URI (if present), while on server-side, it will be calculated from
 * the enviornment. As such, this trait exists to provide the common code
 * between both client-side and server-side requests, and each can then
 * use the headers functionality required by their implementations.
 */
trait RequestTrait
{
    /**
     * @var string
     */
    private $method;

    /**
     * The request-target, if it has been provided or calculated.
     *
     * @var null|string
     */
    private $requestTarget;

    /**
     * @var null|UriInterface
     */
    private $uri;

    /**
     * Supported HTTP methods
     *
     * @var array
     */
    private $validMethods = [
        'CONNECT',
        'DELETE',
        'GET',
        'HEAD',
        'OPTIONS',
        'PATCH',
        'POST',
        'PUT',
        'TRACE',
    ];

    /**
     * Initialize request state.
     *
     * Used by constructors.
     *
     * @param null|string $uri URI for the request, if any.
     * @param null|string $method HTTP method for the request, if any.
     * @param string|resource|StreamableInterface $body Message body, if any.
     * @param array $headers Headers for the message, if any.
     * @throws InvalidArgumentException for any invalid value.
     */
    private function initialize($uri = null, $method = null, $body = 'php://memory', array $headers = [])
    {
        if (! $uri instanceof UriInterface && ! is_string($uri) && null !== $uri) {
            throw new InvalidArgumentException(
                'Invalid URI provided; must be null, a string, or a Psr\Http\Message\UriInterface instance'
            );
        }

        $this->validateMethod($method);

        if (! is_string($body) && ! is_resource($body) && ! $body instanceof StreamableInterface) {
            throw new InvalidArgumentException(
                'Body must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamableInterface implementation'
            );
        }

        if (is_string($uri)) {
            $uri = new Uri($uri);
        }

        $this->method = $method;
        $this->uri    = $uri;
        $this->stream = ($body instanceof StreamableInterface) ? $body : new Stream($body, 'r');

        list($this->headerNames, $this->headers) = $this->filterHeaders($headers);
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }

        if (! $this->uri) {
            return '/';
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    /**
     * Create a new instance with a specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-2.7 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return self
     * @throws InvalidArgumentException if the request target is invalid.
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Create a new instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * changed request method.
     *
     * @param string $method Case-insensitive method.
     * @return self
     * @throws InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $this->validateMethod($method);
        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request, if any.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Create a new instance with the provided URI.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @return self
     */
    public function withUri(UriInterface $uri)
    {
        $new = clone $this;
        $new->uri = $uri;
        return $new;
    }

    /**
     * Validate the HTTP method
     *
     * @param null|string $method
     * @throws InvalidArgumentException on invalid HTTP method.
     */
    private function validateMethod($method)
    {
        if (null === $method) {
            return true;
        }

        if (! is_string($method)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $method = strtoupper($method);

        if (! in_array($method, $this->validMethods, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }
    }

    /**
     * Retrieve the host from the URI instance
     * 
     * @return string
     */
    private function getHostFromUri()
    {
        $host  = $this->uri->getHost();
        $host .= $this->uri->getPort() ? ':' . $this->uri->getPort() : '';
        return $host;
    }
}
