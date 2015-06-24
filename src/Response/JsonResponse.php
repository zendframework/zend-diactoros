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
use Zend\Diactoros\Stream;

/**
 * HTML response.
 *
 * Allows creating a response by passing an HTML string to the constructor;
 * by default, sets a status code of 200 and sets the Content-Type header to
 * text/html.
 */
class JsonResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Create a JSON response with the given array of data.
     *
     * If the data provided is null, an empty ArrayObject is used; if the data
     * is scalar, it is cast to an array prior to serialization.
     *
     * @param string $data Data to convert to JSON.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     */
    public function __construct($data, $status = 200, array $headers = [])
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($this->jsonEncode($data));

        $headers = $this->injectContentType('application/json', $headers);

        parent::__construct($body, $status, $headers);
    }

    /**
     * Encode the provided data to JSON.
     *
     * @param mixed $data
     * @return string
     */
    private function jsonEncode($data)
    {
        if ($data === null) {
            // Use an ArrayObject to force an empty JSON object.
            $data = new ArrayObject();
        }

        if (is_scalar($data)) {
            $data = (array) $data;
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }
}
