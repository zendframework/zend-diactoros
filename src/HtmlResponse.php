<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

use Psr\Http\Message\ResponseInterface;

/**
 * HTML response.
 *
 * Subclass for easier creation of responses with already existing string content.
 */
class HtmlResponse extends Response implements ResponseInterface
{
    /**
     * Create a response with the given body text.
     *
     * @param string $html The response body content, as a string.
     * @param int $status Status code for the response, if any.
     * @param array $headers Headers for the response, if any.
     */
    public function __construct($html, $status = 200, array $headers = [])
    {
        parent::__construct('php://memory', $status, $headers);
        $this->getBody()->write($html);
    }
}
