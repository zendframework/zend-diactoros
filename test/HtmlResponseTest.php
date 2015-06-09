<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Diactoros\HtmlResponse;

class HtmlResponseTest extends TestCase
{
    public function testShortConstructor()
    {
        $body = 'Uh oh not found';
        $status = 404;
        $headers = [
            'x-custom' => [ 'foo-bar' ],
        ];

        $response = new HtmlResponse($body, $status, $headers);
        $this->assertSame($body, $response->getBody()->__toString());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals($headers, $response->getHeaders());
    }
}
