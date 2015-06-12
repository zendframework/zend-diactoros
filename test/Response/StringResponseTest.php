<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros\Response;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Diactoros\Response\StringResponse;

class StringResponseTest extends TestCase
{
    public function testHtmlConstructor()
    {
        $body = '<html>Uh oh not found</html>';
        $status = 404;
        $headers = [
            'x-custom' => [ 'foo-bar' ],
        ];

        $response = StringResponse::html($body, $status, $headers);
        $this->assertInstanceOf('Zend\Diactoros\Response', $response);
        $this->assertSame($body, $response->getBody()->__toString());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(['foo-bar'], $response->getHeader('x-custom'));
        $this->assertEquals('text/html', $response->getHeaderLine('content-type'));
    }

    public function testJsonConstructor()
    {
        $data = [
            'nested' => [
                'json' => [
                    'tree'
                ]
            ]
        ];
        $json = '{"nested":{"json":["tree"]}}';

        $response = StringResponse::json($data);
        $this->assertInstanceOf('Zend\Diactoros\Response', $response);
        $this->assertSame($json, $response->getBody()->__toString());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
    }

    public function testContentTypeCanBeOverwritten()
    {
        $data = null;
        $json = '{}';

        $response = StringResponse::json($data, 200, ['content-type' => 'foo/json']);
        $this->assertSame($json, $response->getBody()->__toString());
        $this->assertEquals('foo/json', $response->getHeaderLine('content-type'));
    }
}
