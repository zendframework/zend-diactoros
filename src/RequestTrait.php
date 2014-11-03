<?php
namespace Phly\Http;

/**
 * Represent an HTTP Request.
 *
 * This trait provides the common methods for accessing the values of an HTTP
 * request, specifically the URL and request method.
 */
trait RequestTrait
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
     * Gets the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
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
}
