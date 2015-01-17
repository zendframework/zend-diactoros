<?php
namespace Phly\Http;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamableInterface;

/**
 * Server-side HTTP request
 *
 * Extends the Request definition to add methods for accessing incoming data,
 * specifically server parameters, cookies, matched path parameters, query
 * string arguments, body parameters, and upload file information.
 *
 * "Attributes" are discovered via decomposing the request (and usually
 * specifically the URI path), and typically will be injected by the application.
 *
 * Requests are considered immutable; all methods that might change state are
 * implemented such that they retain the internal state of the current
 * message and return a new instance that contains the changed state.
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    use MessageTrait;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var array
     */
    private $bodyParams;

    /**
     * @var array
     */
    private $cookieParams;

    /**
     * @var array
     */
    private $fileParams;

    /**
     * @var array
     */
    private $queryParams;

    /**
     * @var array
     */
    private $serverParams;

    /**
     * @param string|resource|StreamableInterface $stream Stream representing message body.
     * @param array $serverParams Server parameters, typically from $_SERVER
     * @param array $fileParams Upload file information; should be in PHP's $_FILES format
     * @return void
     */
    public function __construct(
        $stream = 'php://input',
        array $serverParams = [],
        array $fileParams = []
    ) {
        $this->setStream($stream);
        $this->serverParams = $serverParams;
        $this->fileParams   = $fileParams;
    }

    /**
     * Retrieve server parameters.
     *
     * Retrieves data related to the incoming request environment,
     * typically derived from PHP's $_SERVER superglobal. The data IS NOT
     * REQUIRED to originate from $_SERVER.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * Retrieve the upload file metadata.
     *
     * This method MUST return file upload metadata in the same structure
     * as PHP's $_FILES superglobal.
     *
     * These values MUST remain immutable over the course of the incoming
     * request. They SHOULD be injected during instantiation, such as from PHP's
     * $_FILES superglobal, but MAY be derived from other sources.
     *
     * @return array Upload file(s) metadata, if any.
     */
    public function getFileParams()
    {
        return $this->fileParams;
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The data MUST be compatible with the structure of the $_COOKIE
     * superglobal.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * Create a new instance with the specified cookies.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated cookie values.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return self
     */
    public function withCookieParams(array $cookies)
    {
        $new = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    /**
     * Retrieve query string arguments.
     *
     * Retrieves the deserialized query string arguments, if any.
     *
     * Note: the query params might not be in sync with the URL or server
     * params. If you need to ensure you are only getting the original
     * values, you may need to parse the composed URL or the `QUERY_STRING`
     * composed in the server params.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Create a new instance with the specified query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's parse_str() would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URL stored by the
     * request, nor the values in the server params.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated query string arguments.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return self
     */
    public function withQueryParams(array $query)
    {
        $new = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request body can be deserialized to an array, this method MAY be
     * used to retrieve them.
     *
     * @return array The deserialized body parameters, if any.
     */
    public function getBodyParams()
    {
        return $this->bodyParams;
    }

    /**
     * Create a new instance with the specified body parameters.
     *
     * These MAY be injected during instantiation from PHP's $_POST
     * superglobal. The data IS NOT REQUIRED to come from $_POST, but MUST be
     * an array. This method can be used during the request lifetime to inject
     * parameters discovered and/or deserialized from the request body; as an
     * example, if content negotiation determines that the request data is
     * a JSON payload, this method could be used to inject the deserialized
     * parameters.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated body parameters.
     *
     * @param array $params The deserialized body parameters.
     * @return self
     */
    public function withBodyParams(array $params)
    {
        $new = clone $this;
        $new->bodyParams = $params;
        return $new;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * The request "attributes" may be used to allow injection of any
     * parameters derived from the request: e.g., the results of path
     * match operations; the results of decrypting cookies; the results of
     * deserializing non-form-encoded message bodies; etc. Attributes
     * will be application and request specific, and CAN be mutable.
     *
     * @return array Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * Retrieves a single derived request attribute as described in
     * getAttributes(). If the attribute has not been previously set, returns
     * the default value as provided.
     *
     * This method obviates the need for a hasAttribute() method, as it allows
     * specifying a default value to return if the attribute is not found.
     *
     * @see getAttributes()
     * @param string $attribute Attribute name.
     * @param mixed $default Default value to return if the attribute does not exist.
     * @return mixed
     */
    public function getAttribute($attribute, $default = null)
    {
        if (! array_key_exists($attribute, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$attribute];
    }

    /**
     * Create a new instance with the specified derived request attribute.
     *
     * This method allows setting a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * updated attribute.
     *
     * @see getAttributes()
     * @param string $attribute The attribute name.
     * @param mixed $value The value of the attribute.
     * @return self
     */
    public function withAttribute($attribute, $value)
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;
        return $new;
    }

    /**
     * Create a new instance that removes the specified derived request
     * attribute.
     *
     * This method allows removing a single derived request attribute as
     * described in getAttributes().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that removes
     * the attribute.
     *
     * @see getAttributes()
     * @param string $attribute The attribute name.
     * @return self
     */
    public function withoutAttribute($attribute)
    {
        if (! isset($this->attributes[$attribute])) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$attribute]);
        return $new;
    }

    /**
     * Proxy to receive the request method.
     *
     * This overrides the parent functionality to ensure the method is never
     * empty; if no method is present, it returns 'GET'.
     *
     * @return string
     */
    public function getMethod()
    {
        $method = parent::getMethod();
        if (empty ($method)) {
            return 'GET';
        }
        return $method;
    }

    /**
     * Set the request method.
     *
     * Unlike the regular Request implementation, the server-side
     * normalizes the method to uppercase to ensure consistency
     * and make checking the method simpler.
     *
     * This methods returns a new instance.
     *
     * @param string $method
     * @return self
     */
    public function withMethod($method)
    {
        return parent::withMethod(strtoupper($method));
    }

    /**
     * Set the body stream
     *
     * @param string|resource|StreamableInterface $stream
     * @return void
     */
    private function setStream($stream)
    {
        if ($stream === 'php://input') {
            $stream = new PhpInputStream();
        }

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

        $this->stream = $stream;
    }
}
