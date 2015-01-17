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
     * Retrieve server params
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
     * This method should return file upload metadata in the same structure
     * as PHP's $_FILES superglobal.
     *
     * The assumption is these are injected during instantiation, typically
     * from PHP's $_FILES superglobal, and should remain immutable over the
     * course of the incoming request.
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
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * Set cookies.
     *
     * Set cookies sent by the client to the server.
     *
     * The data IS NOT REQUIRED to come from the $_COOKIE superglobal, but MUST
     * be compatible with the structure of $_COOKIE. Typically, this data will
     * be injected at instantiation.
     *
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return void
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
     * The assumption is these are injected during instantiation, typically
     * from PHP's $_GET superglobal, and should remain immutable over the
     * course of the incoming request.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Set query string arguments.
     *
     * These values SHOULD remain immutable over the course of the incoming
     * request. They MAY be injected during instantiation, such as from PHP's
     * $_GET superglobal, or MAY be derived from some other value such as the
     * URI. In cases where the arguments are parsed from the URI, the data
     * MUST be compatible with what PHP's `parse_str()` would return for
     * purposes of how duplicate query parameters are handled, and how nested
     * sets are handled.
     *
     * Setting query string arguments MUST NOT change the URL stored by the
     * request, nor the values in the server params.
     *
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return void
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
     * If the request body can be deserialized, and if the deserialized values
     * can be represented as an array, this method can be used to
     * retrieve them.
     *
     * In other cases, the parent getBody() method should be used to retrieve
     * the body content.
     *
     * @return array The deserialized body parameters, if any.
     */
    public function getBodyParams()
    {
        return $this->bodyParams;
    }

    /**
     * Set parameters provided in the request body.
     *
     * These MAY be injected during instantiation from PHP's $_POST
     * superglobal. The data IS NOT REQUIRED to come from $_POST, but MUST be
     * an array. This method can be used during the request lifetime to inject
     * parameters discovered and/or deserialized from the request body; as an
     * example, if content negotiation determines that the request data is
     * a JSON payload, this method could be used to inject the deserialized
     * parameters.
     *
     * @param array $params The deserialized body parameters.
     * @return void
     */
    public function withBodyParams(array $params)
    {
        $new = clone $this;
        $new->bodyParams = $params;
        return $new;
    }

    /**
     * Retrieve attributes derived from the request
     *
     * If a router or similar is used to match against the path and/or request,
     * this method can be used to retrieve the results, so long as those
     * results can be represented as an array.
     *
     * @return array Path parameters matched by routing
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single attribute by name.
     *
     * If the attribute is not present, return the value provided in $default
     * instead.
     *
     * @param string $attribute
     * @param mixed $default
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
     * Set a single named attribute
     *
     * @param string $attribute
     * @param mixed $value
     * @return self
     */
    public function withAttribute($attribute, $value)
    {
        $new = clone $this;
        $new->attributes[$attribute] = $value;
        return $new;
    }

    /**
     * Remove a single named attribute
     *
     * If the attribute did not exist, returns the current instance; otherwise,
     * returns a new instance that removes the given attribute.
     *
     * @param string $attribute
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
     * @param string $method
     * @return void
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
