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
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\Response;

/**
 * Produce a Json response.
 */
class JsonResponse extends Response
{
    /**
     * Create a Json response.
     *
     * Produces a JSON response, serialized from $data passed in parameter.
     * MIME Content-type is application/json
     *
     * @param mixed $data The response data, to be serialized in JSON.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @param int $encodingOptions JSON Encoding parameters. Defaults to JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT (RFC4627-compliant JSON, which may also be embedded into HTML)
     * @throws \Exception
     */
    public function __construct($data, $status = 200, array $headers = [], $encodingOptions = 15)
    {
        $errorHandler = null;
        $errorHandler = set_error_handler(function () use (&$errorHandler) {
            if (JSON_ERROR_NONE !== json_last_error()) {
                return;
            }
            if ($errorHandler) {
                call_user_func_array($errorHandler, func_get_args());
            }
        });
        try {
            // Clear json_last_error()
            json_encode(null);
            $json = json_encode($data, $encodingOptions);
            restore_error_handler();
        } catch (\Exception $exception) {
            restore_error_handler();
            throw $exception;
        }
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        if (!isset($headers['content-type'])) {
            $headers['content-type'] = 'application/json';
        }

        parent::__construct('php://temp', $status, $headers);
        $this->getBody()->write($json);
    }
}