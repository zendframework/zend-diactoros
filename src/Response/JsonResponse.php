<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Response;

use InvalidArgumentException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

/**
 * JSON response.
 *
 * Allows creating a response by passing data to the constructor; by default,
 * serializes the data to JSON, sets a status code of 200 and sets the
 * Content-Type header to application/json.
 */
class JsonResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Create a JSON response with the given data.
     *
     * Default JSON encoding is performed with the following options, which
     * produces RFC4627-compliant JSON, capable of embedding into HTML.
     *
     * - JSON_HEX_TAG
     * - JSON_HEX_APOS
     * - JSON_HEX_AMP
     * - JSON_HEX_QUOT
     *
     * @param array $data Data to convert to JSON.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @param int $encodingOptions JSON encoding options to use.
     * @throws InvalidArgumentException if unable to encode the $data to JSON.
     */
    public function __construct($data, $status = 200, array $headers = [], $encodingOptions = 15)
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($this->jsonEncode($data, $encodingOptions));

        $headers = $this->injectContentType('application/json', $headers);

        parent::__construct($body, $status, $headers);
    }

    /**
     * Encode the provided data to JSON.
     *
     * @param mixed $data
     * @param int $encodingOptions
     * @return string
     * @throws InvalidArgumentException if unable to encode the $data to JSON.
     */
    private function jsonEncode($data, $encodingOptions)
    {
        if (is_resource($data)) {
            throw new InvalidArgumentException('Cannot JSON encode resources');
        }

        // Clear json_last_error()
        json_encode(null);

        $json = json_encode($data, $encodingOptions);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(sprintf(
                'Unable to encode data to JSON in %s: %s',
                __CLASS__,
                json_last_error_msg()
            ));
        }

        return $json;
    }
}
