<?php
namespace Phly\Http;

use ArrayAccess;
use InvalidArgumentException;
use Psr\Http\Message\IncomingRequestInterface;

/**
 * Incoming (server-side) HTTP request
 *
 * Extends the Request definition to add methods for accessing incoming data,
 * specifically cookies, matched path parameters, query string arguments, body
 * parameters, and upload file information.
 *
 * Cookies may be overwritten later; this facility may be useful for enabling
 * cookie encryption.
 *
 * Body parameters are often only available after performing content negotiation
 * and deserialization of the request body; as such, they, too, may be injected
 * after instantiation.
 *
 * Path parameters are discovered via decomposing the request (and usually
 * specifically the URI path), and will be injected by the application.
 */
class IncomingRequest extends Request implements IncomingRequestInterface
{
    /**
     * @var array|object
     */
    private $bodyParams = [];

    /**
     * @var array|ArrayAccess
     */
    private $cookieParams;

    /**
     * @var array|ArrayAccess
     */
    private $fileParams;

    /**
     * @var array|ArrayAccess
     */
    private $pathParams = [];

    /**
     * @var array|ArrayAccess
     */
    private $queryParams;

    /**
     * @param string|\Psr\Http\Message\StreamableInterface|array $stream Stream representing message body.
     *        Alternately, this can be an array with keys for each of the possible arguments.
     * @param array|ArrayAccess $cookieParams Deserialized cookies
     * @param array|ArrayAccess $pathParams Variables matched from the URI path
     * @param array|ArrayAccess $queryParams Deserialized query string arguments
     * @param array|ArrayAccess $bodyParams Deserialized body parameters
     * @param array|ArrayAccess $fileParams Upload file information; should be in PHP's $_FILES format
     * @return void
     */
    public function __construct(
        $stream = 'php://input',
        $cookieParams = [],
        $pathParams = [],
        $queryParams = [],
        $bodyParams = [],
        $fileParams = []
    ) {
        if (is_array($stream)) {
            if (isset($stream['cookieParams']) && empty($cookieParams)) {
                $cookieParams = $stream['cookieParams'];
            }
            if (isset($stream['pathParams']) && empty($pathParams)) {
                $pathParams = $stream['pathParams'];
            }
            if (isset($stream['queryParams']) && empty($queryParams)) {
                $queryParams = $stream['queryParams'];
            }
            if (isset($stream['bodyParams']) && empty($bodyParams)) {
                $bodyParams = $stream['bodyParams'];
            }
            if (isset($stream['fileParams']) && empty($fileParams)) {
                $fileParams = $stream['fileParams'];
            }

            $stream = isset($stream['stream']) ? $stream['stream'] : 'php://input';
        }

        parent::__construct($stream);
        $this->setCookieParams($cookieParams);
        $this->setPathParams($pathParams);
        $this->setQueryParams($queryParams);
        $this->setBodyParams($bodyParams);
        $this->setFileParams($fileParams);
    }

    /**
     * Retrieve cookies.
     *
     * Retrieves cookies sent by the client to the server.
     *
     * The assumption is these are injected during instantiation, typically
     * from PHP's $_COOKIE superglobal, and should remain immutable over the
     * course of the incoming request.
     *
     * The return value can be either an array or an object that acts like
     * an array (e.g., implements ArrayAccess, or an ArrayObject).
     * 
     * @return array|ArrayAccess
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * Set cookie parameters.
     *
     * Allows a library to set the cookie parameters. Use cases include
     * libraries that implement additional security practices, such as
     * encrypting or hashing cookie values; in such cases, they will read
     * the original value, filter them, and re-inject into the incoming
     * request..
     *
     * The value provided should be an array or array-like object
     * (e.g., implements ArrayAccess, or an ArrayObject).
     * 
     * @param array|ArrayAccess $cookies Cookie values/structs
     * 
     * @return void
     */
    public function setCookieParams($cookies)
    {
        if (! is_array($cookies) && ! $cookies instanceof ArrayAccess) {
            throw new InvalidArgumentException(
                'Cookies must be provided as either an array or ArrayAccess'
            );
        }

        $this->cookieParams = $cookies;
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
     * The return value can be either an array or an object that acts like
     * an array (e.g., implements ArrayAccess, or an ArrayObject).
     * 
     * @return array|ArrayAccess
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Set query parameters
     *
     * Internal method only.
     * 
     * @param array|ArrayAccess $queryParams 
     */
    private function setQueryParams($queryParams)
    {
        if (! is_array($queryParams) && ! $queryParams instanceof ArrayAccess) {
            throw new InvalidArgumentException(
                'Query string arguments must be provided as either an array or ArrayAccess'
            );
        }

        $this->queryParams = $queryParams;
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
     * The return value can be either an array or an object that acts like
     * an array (e.g., implements ArrayAccess, or an ArrayObject).
     * 
     * @return array|ArrayAccess Upload file(s) metadata, if any.
     */
    public function getFileParams()
    {
        return $this->fileParams;
    }

    /**
     * Set upload file data.
     *
     * Internal method only.
     * 
     * @param array|ArrayAccess $fileParams 
     */
    private function setFileParams($fileParams)
    {
        if (! is_array($fileParams) && ! $fileParams instanceof ArrayAccess) {
            throw new InvalidArgumentException(
                'Files must be provided as either an array or ArrayAccess'
            );
        }

        $this->fileParams = $fileParams;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * If the request body can be deserialized, and if the deserialized values
     * can be represented as an array or object, this method can be used to
     * retrieve them.
     *
     * In other cases, the parent getBody() method should be used to retrieve
     * the body content.
     * 
     * @return array|object The deserialized body parameters, if any. These may
     *                      be either an array or an object, though an array or
     *                      array-like object is recommended.
     */
    public function getBodyParams()
    {
        return $this->bodyParams;
    }

    /**
     * Set the request body parameters.
     *
     * If the body content can be deserialized, the values obtained may then
     * be injected into the response using this method. This method will
     * typically be invoked by a factory marshaling request parameters.
     * 
     * @param array|object $values The deserialized body parameters, if any.
     *                             These may be either an array or an object,
     *                             though an array or array-like object is
     *                             recommended.
     *
     * @return void
     */
    public function setBodyParams($values)
    {
        if (! is_array($values) && ! is_object($values)) {
            throw new InvalidArgumentException(
                'Body parameters must be provided as either an array or an object'
            );
        }

        $this->bodyParams = $values;
    }

    /**
     * Retrieve parameters matched during routing.
     *
     * If a router or similar is used to match against the path and/or request,
     * this method can be used to retrieve the results, so long as those
     * results can be represented as an array or array-like object.
     *
     * @return array|ArrayAccess Path parameters matched by routing
     */
    public function getPathParams()
    {
        return $this->pathParams;
    }

    /**
     * Set parameters discovered by matching that path
     *
     * If a router or similar is used to match against the path and/or request,
     * this method can be used to inject the request with the results, so long
     * as those results can be represented as an array or array-like object.
     * 
     * @param array|ArrayAccess $values Path parameters matched by routing
     *
     * @return void
     */
    public function setPathParams(array $values)
    {
        if (! is_array($values) && ! $values instanceof ArrayAccess) {
            throw new InvalidArgumentException(
                'Path parameters must be provided as either an array or ArrayAccess'
            );
        }

        $this->pathParams = $values;
    }
}
