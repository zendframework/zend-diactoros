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
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\StringResponse;
use Zend\Diactoros\Stream;

class HtmlResponseTest extends TestCase
{
    public function testHtmlConstructor()
    {
        $body = '<html>Uh oh not found</html>';
        $status = 404;
        $headers = [
            'x-custom' => [ 'foo-bar' ],
        ];

        $response = new HtmlResponse($body, $status, $headers);
        $this->assertSame($body, (string) $response->getBody());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(['foo-bar'], $response->getHeader('x-custom'));
        $this->assertEquals('text/html', $response->getHeaderLine('content-type'));
    }

    public function testHtmlConstructorWithStream()
    {
        $body = '<html>Hello world</html>';
        $stream = new Stream("php://memory", "wb+");
        $stream->write($body);

        $response = new HtmlResponse($stream);
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testContentTypeCanBeOverwritten()
    {
        $body = '<html>Uh oh not found</html>';

        $response = new HtmlResponse($body, 200, ['content-type' => 'foo/html']);
        $this->assertSame($body, (string) $response->getBody());
        $this->assertEquals('foo/html', $response->getHeaderLine('content-type'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidContent()
    {
        new HtmlResponse(new \stdClass());
    }
}
