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
 * A class with helper methods for easily creating proper responses from strings or structured data.
 */
final class StringResponse
{
    /**
     * Create a HTML response with the given body text.
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
     * @param array|\JsonSerializable $data The data to be converted to JSON.
     * @param int $status Status code for the response, if any.
     * @param array $headers Headers for the response, if any.
     * @return Response
     */
    public static function json($data, $status = 200, array $headers = [])
    {
        if ($data === null) {
            $data = new ArrayObject();
        }

        $json = json_encode($data, JSON_UNESCAPED_SLASHES);

        return static::createResponse($json, $status, $headers, 'application/json');
    }

    private function __construct()
    {
    }

    private static function createResponse($body, $status, array $headers, $contentType)
    {
        $response = new Response('php://temp', $status, $headers);
        $response->getBody()->write($body);

        if (! $response->hasHeader('content-type')) {
            $response = $response->withHeader('content-type', $contentType);
        }

        return $response;
    }
}
