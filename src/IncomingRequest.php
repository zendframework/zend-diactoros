<?php
namespace Phly\Http;

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
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $bodyParams = [];

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
     * @param string|\Psr\Http\Message\StreamableInterface|array $stream
     *     Stream representing message body. Alternately, this can be an 
     *     array with keys for each of the possible arguments.
     * @param array $cookieParams Deserialized cookies
     * @param array $attributes Attributes derived from the request
     * @param array $queryParams Deserialized query string arguments
     * @param array $bodyParams Deserialized body parameters
     * @param array $fileParams Upload file information; should be in PHP's $_FILES format
     * @return void
     */
    public function __construct(
        $stream = 'php://input',
        array $cookieParams = [],
        array $attributes = [],
        array $queryParams = [],
        array $bodyParams = [],
        array $fileParams = []
    ) {
        if (is_array($stream)) {
            if (isset($stream['cookieParams']) && empty($cookieParams)) {
                $cookieParams = $stream['cookieParams'];
            }
            if (isset($stream['attributes']) && empty($attributes)) {
                $attributes = $stream['attributes'];
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
        $this->setAttributes($attributes);
        $this->setQueryParams($queryParams);
        $this->setBodyParams($bodyParams);
        $this->setFileParams($fileParams);
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
     * Set cookie parameters.
     *
     * Allows a library to set the cookie parameters. Use cases include
     * libraries that implement additional security practices, such as
     * encrypting or hashing cookie values; in such cases, they will read
     * the original value, filter them, and re-inject into the incoming
     * request..
     *
     * @param array $cookies Cookie values/structs
     */
    public function setCookieParams(array $cookies)
    {
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
     * @return array
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
     * @param array $queryParams 
     */
    private function setQueryParams(array $queryParams)
    {
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
     * @return array Upload file(s) metadata, if any.
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
     * @param array $fileParams 
     */
    private function setFileParams(array $fileParams)
    {
        $this->fileParams = $fileParams;
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
     * Set the request body parameters.
     *
     * If the body content can be deserialized as an array, the values obtained may then
     * be injected into the response using this method. This method will
     * typically be invoked by a factory marshaling request parameters.
     * 
     * @param array $values The deserialized body parameters, if any.
     */
    public function setBodyParams(array $values)
    {
        $this->bodyParams = $values;
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
}
