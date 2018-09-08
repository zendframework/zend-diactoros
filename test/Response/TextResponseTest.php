<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros\Response;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response\TextResponse;

class TextResponseTest extends TestCase
{
    public function testConstructorAcceptsBodyAsString()
    {
        $body = 'Uh oh not found';

        $response = new TextResponse($body);
        $this->assertSame($body, (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConstructorAllowsPassingStatus()
    {
        $body = 'Uh oh not found';
        $status = 404;

        $response = new TextResponse($body, $status);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testConstructorAllowsPassingHeaders()
    {
        $body = 'Uh oh not found';
        $status = 404;
        $headers = [
            'x-custom' => [ 'foo-bar' ],
        ];

        $response = new TextResponse($body, $status, $headers);
        $this->assertSame(['foo-bar'], $response->getHeader('x-custom'));
        $this->assertSame('text/plain; charset=utf-8', $response->getHeaderLine('content-type'));
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame($body, (string) $response->getBody());
    }

    public function testAllowsStreamsForResponseBody()
    {
        $stream = $this->prophesize(StreamInterface::class);
        $body   = $stream->reveal();
        $response = new TextResponse($body);
        $this->assertSame($body, $response->getBody());
    }

    public function invalidContent()
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
     * @dataProvider invalidContent
     */
    public function testRaisesExceptionforNonStringNonStreamBodyContent($body)
    {
        $this->expectException(InvalidArgumentException::class);

        new TextResponse($body);
    }

    /**
     * @group 115
     */
    public function testConstructorRewindsBodyStream()
    {
        $text = 'test data';
        $response = new TextResponse($text);

        $actual = $response->getBody()->getContents();
        $this->assertSame($text, $actual);
    }
}
