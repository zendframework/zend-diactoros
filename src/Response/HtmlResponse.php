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
 * Produce a HTML response.
 */
class HtmlResponse extends Response
{
    /**
     * Create a HTML response.
     *
     * Produces a simple response with the string passed in parameter.
     * MIME Content-type is text/html
     *
     * @param string|StreamInterface $body The body of the response.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     */
    public function __construct($body, $status = 200, array $headers = [])
    {
        if (! is_string($body) && ! $body instanceof StreamInterface) {
            throw new InvalidArgumentException(sprintf(
                'Body provided to %s MUST be a string or Psr\Http\Message\StreamInterface instance; received "%s"',
                __CLASS__,
                (is_object($body) ? get_class($body) : gettype($body))
            ));
        }

        if (!isset($headers['content-type'])) {
            $headers['content-type'] = 'text/html';
        }

        if ($body instanceof StreamInterface) {
            parent::__construct($body, $status, $headers);
        } else {
            parent::__construct('php://temp', $status, $headers);
            $this->getBody()->write($body);
        }
    }
}
