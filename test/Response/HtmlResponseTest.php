<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros\Response;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response\HtmlResponse;

class HtmlResponseTest extends TestCase
{
    public function testConstructorAcceptsHtmlString()
    {
        $body = '<html>Uh oh not found</html>';

        $response = new HtmlResponse($body);
        $this->assertSame($body, (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testConstructorAllowsPassingStatus()
    {
        $body = '<html>Uh oh not found</html>';
        $status = 404;

        $response = new HtmlResponse($body, $status);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testConstructorAllowsPassingHeaders()
    {
        $body = '<html>Uh oh not found</html>';
        $status = 404;
        $headers = [
            'x-custom' => [ 'foo-bar' ],
        ];

        $response = new HtmlResponse($body, $status, $headers);
        $this->assertEquals(['foo-bar'], $response->getHeader('x-custom'));
        $this->assertEquals('text/html; charset=utf-8', $response->getHeaderLine('content-type'));
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testAllowsStreamsForResponseBody()
    {
        $stream = $this->prophesize('Psr\Http\Message\StreamInterface');
        $body   = $stream->reveal();
        $response = new HtmlResponse($body);
        $this->assertSame($body, $response->getBody());
    }

    public function invalidHtmlContent()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['php://temp']],
            'object'     => [(object) ['php://temp']],
        ];
    }

    /**
     * @dataProvider invalidHtmlContent
     * @expectedException InvalidArgumentException
     */
    public function testRaisesExceptionforNonStringNonStreamBodyContent($body)
    {
        $response = new HtmlResponse($body);
    }

    public function testConstructorRewindsBodyStream()
    {
        $html = '<p>test data</p>';
        $response = new HtmlResponse($html);

        $actual = $response->getBody()->getContents();
        $this->assertEquals($html, $actual);
    }
}
