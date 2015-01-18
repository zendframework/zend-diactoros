<?php
namespace Phly\Http;

use InvalidArgumentException;
use Psr\Http\Message\IncomingRequestInterface;
use Psr\Http\Message\StreamableInterface;

/**
 * Incoming (server-side) HTTP request
 *
 * Extends the Request definition to add methods for accessing incoming data,
 * specifically server parameters, cookies, matched path parameters, query 
 * string arguments, body parameters, and upload file information.
 *
 * "Attributes" are discovered via decomposing the request (and usually
 * specifically the URI path), and typically will be injected by the application.
 */
class IncomingRequest implements IncomingRequestInterface
{
    use MessageTrait, RequestTrait, ImmutableHeadersTrait;

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
     * @param string $url URL for the incoming request.
     * @param string $method HTTP request method.
     * @param array $headers HTTP headers.
     * @param string|StreamableInterface|array $stream
     *     Stream representing message body. Alternately, this can be an 
     *     array with keys for each of the possible arguments.
     * @param array $serverParams Server parameters
     * @param array $cookieParams Deserialized cookies
     * @param array $queryParams Deserialized query string arguments
     * @param array $bodyParams Deserialized body parameters
     * @param array $fileParams Upload file information; should be in PHP's $_FILES format
     * @param array $attributes Attributes derived from the request
     * @param string $protocolVersion HTTP protocol version; if not provided,
     *     will attempt to determine it from the $serverParams, and will default
     *     to 1.1 if auto-detection fails.
     * @return void
     */
    public function __construct(
        $url,
        $method = null,
        array $headers = [],
        $stream = 'php://input',
        array $serverParams = [],
        array $cookieParams = [],
        array $queryParams = [],
        array $bodyParams = [],
        array $fileParams = [],
        array $attributes = [],
        $protocolVersion = null
    ) {
        if (is_array($url)) {
            if (isset($url['method']) && empty($method)) {
                $method = $url['method'];
            }
            if (isset($url['headers']) && is_array($url['headers']) && empty($headers)) {
                $headers = $url['headers'];
            }
            if (isset($url['server']) && empty($serverParams)) {
                $serverParams = $url['server'];
            }
            if (isset($url['cookie']) && empty($cookieParams)) {
                $cookieParams = $url['cookie'];
            }
            if (isset($url['query']) && empty($queryParams)) {
                $queryParams = $url['query'];
            }
            if (isset($url['body']) && empty($bodyParams)) {
                $bodyParams = $url['body'];
            }
            if (isset($url['file']) && empty($fileParams)) {
                $fileParams = $url['file'];
            }
            if (isset($url['attributes']) && empty($attributes)) {
                $attributes = $url['attributes'];
            }
            if (isset($url['protocol']) && empty($protocolVersion)) {
                $protocolVersion = $url['protocol'];
            }

            $url = isset($url['url']) ? $url['url'] : null;
        }

        $this->setUrl($url);
        $this->setMethod($method);
        $this->setHeaders($headers);
        $this->setStream($stream);
        $this->setServerParams($serverParams);
        $this->setCookieParams($cookieParams);
        $this->setQueryParams($queryParams);
        $this->setBodyParams($bodyParams);
        $this->setFileParams($fileParams);
        $this->setAttributes($attributes);
        $this->setProtocolVersion($protocolVersion);
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
     * Set parameters discovered by matching that path
     *
     * If a router or similar is used to match against the path and/or request,
     * this method can be used to inject them, so long as those
     * results can be represented as an array.
     * 
     * @param array $values Path parameters matched by routing
     */
    public function setAttributes(array $values)
    {
        $this->attributes = $values;
    }

    /**
     * Set a single named attribute
     * 
     * @param string $attribute 
     * @param mixed $value 
     * @return void
     */
    public function setAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;
    }

    /**
     * Set the request url.
     * 
     * @param string $url 
     * @return void
     * @throws InvalidArgumentException for missing or invalid URLs.
     */
    private function setUrl($url)
    {
        if (empty($url)) {
            throw new InvalidArgumentException('No URL provided to incoming request!');
        }

        $uri = new Uri($url);
        if (! $uri->isValid()) {
            throw new InvalidArgumentException('Invalid URL provided to incoming request!');
        }

        $this->url = $uri;
    }

    /**
     * Set the request method.
     *
     * Normalizes to uppercase.
     * 
     * @param string $method 
     * @return void
     */
    private function setMethod($method)
    {
        $method = $method ?: 'GET';
        $this->method = strtoupper($method);
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

    /**
     * Set server parameters.
     *
     * Allows a library to set the server parameters, usually from $_SERVER.
     *
     * Internal method only.
     *
     * @param array $params Server parameters
     */
    private function setServerParams(array $params)
    {
        $this->serverParams = $params;
    }

    /**
     * Set cookie parameters.
     *
     * Allows a library to set the cookie parameters, usually from $_COOKIE.
     *
     * Internal method only.
     *
     * @param array $cookies Cookie values/structs
     */
    private function setCookieParams(array $cookies)
    {
        $this->cookieParams = $cookies;
    }

    /**
     * Set query parameters
     *
     * Internal method only.
     * 
     * @param array $queryParams 
     */
    private function setQueryParams(array $queryParams)
    {
        $this->queryParams = $queryParams;
    }

    /**
     * Set the request body parameters.
     *
     * If the body content can be deserialized as an array, the values obtained 
     * may then be injected into the request.
     *
     * Internal method only.
     * 
     * @param array $values The deserialized body parameters, if any.
     */
    public function setBodyParams(array $values)
    {
        $this->bodyParams = $values;
    }

    /**
     * Set upload file data.
     *
     * Internal method only.
     * 
     * @param array $fileParams 
     */
    private function setFileParams(array $fileParams)
    {
        $this->fileParams = $fileParams;
    }

    /**
     * Set the HTTP protocol version
     * 
     * @param mixed $protocolVersion 
     * @return void
     */
    private function setProtocolVersion($protocolVersion)
    {
        if ($protocolVersion) {
            $this->protocol = $protocolVersion;
            return;
        }

        if (isset($this->serverParams['SERVER_PROTOCOL'])
            && $this->serverParams['SERVER_PROTOCOL']
        ) {
            $this->protocol = $this->serverParams['SERVER_PROTOCOL'];
            return;
        }

        $this->protocol = '1.1';
    }
}
