<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Response;

use ArrayObject;
use Zend\Diactoros\Response;

/**
 * String response factory.
 *
 * A class with helper methods for easily creating proper responses from
 * strings or structured data.
 */
final class StringResponse
{
    /**
     * Create an HTML response with the given body text.
     *
     * @param string $html The response body content, as a string.
     * @param int $status Status code for the response, if any.
     * @param array $headers Headers for the response, if any.
     * @return Response
     */
    public static function html($html, $status = 200, array $headers = [])
    {
        return static::createResponse($html, $status, $headers, 'text/html');
    }

    /**
     * Create a JSON response with the given array of data.
     *
     * If the data provided is null, an empty ArrayObject is used; if the data
     * is scalar, it is cast to an array prior to serialization.
     *
     * @param mixed $data The data to be converted to JSON.
     * @param int $status Status code for the response, if any.
     * @param array $headers Headers for the response, if any.
     * @return Response
     */
    public static function json($data, $status = 200, array $headers = [])
    {
        if ($data === null) {
            // Use an ArrayObject to force an empty JSON object.
            $data = new ArrayObject();
        }

        if (is_scalar($data)) {
            $data = (array) $data;
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES);

        return static::createResponse($json, $status, $headers, 'application/json');
    }

    /**
     * This class is non-instantiable.
     */
    private function __construct()
    {
    }

    /**
     * Create a Response from the provided information.
     *
     * Creates a Response using a php://temp stream, and writes the provided
     * body to the stream; if non content-type header was provided, the given
     * $contentType is injected for it.
     *
     * @param string $body
     * @param int $status
     * @param array $headers
     * @param string $contentType
     * @return Response
     */
    private static function createResponse($body, $status, array $headers, $contentType)
    {
        $response = new Response('php://temp', $status, $headers);
        $response->getBody()->write($body);

        if ($response->hasHeader('content-type')) {
            return $response;
        }

        return $response->withHeader('content-type', $contentType);
    }
}
