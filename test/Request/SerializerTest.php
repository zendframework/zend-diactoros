<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros\Request;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;
use Zend\Diactoros\RelativeStream;
use Zend\Diactoros\Request;
use Zend\Diactoros\Request\Serializer;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;

use function json_encode;
use function strlen;

class SerializerTest extends TestCase
{
    public function testSerializesBasicRequest()
    {
        $request = (new Request())
            ->withMethod('GET')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('Accept', 'text/html');

        $message = Serializer::toString($request);
        $this->assertSame(
            "GET /foo/bar?baz=bat HTTP/1.1\r\nHost: example.com\r\nAccept: text/html",
            $message
        );
    }

    public function testSerializesRequestWithBody()
    {
        $body   = json_encode(['test' => 'value']);
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($body);

        $request = (new Request())
            ->withMethod('POST')
            ->withUri(new Uri('http://example.com/foo/bar'))
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody($stream);

        $message = Serializer::toString($request);
        $this->assertContains("POST /foo/bar HTTP/1.1\r\n", $message);
        $this->assertContains("\r\n\r\n" . $body, $message);
    }

    public function testSerializesMultipleHeadersCorrectly()
    {
        $request = (new Request())
            ->withMethod('GET')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat');

        $message = Serializer::toString($request);
        $this->assertContains("X-Foo-Bar: Baz", $message);
        $this->assertContains("X-Foo-Bar: Bat", $message);
    }

    public function originForms()
    {
        return [
            'path-only'      => [
                'GET /foo HTTP/1.1',
                '/foo',
                ['getPath' => '/foo'],
            ],
            'path-and-query' => [
                'GET /foo?bar HTTP/1.1',
                '/foo?bar',
                ['getPath' => '/foo', 'getQuery' => 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider originForms
     */
    public function testCanDeserializeRequestWithOriginForm($line, $requestTarget, $expectations)
    {
        $message = $line . "\r\nX-Foo-Bar: Baz\r\n\r\nContent";
        $request = Serializer::fromString($message);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame($requestTarget, $request->getRequestTarget());

        $uri = $request->getUri();
        foreach ($expectations as $method => $expect) {
            $this->assertSame($expect, $uri->{$method}());
        }
    }

    public function absoluteForms()
    {
        return [
            'path-only'      => [
                'GET http://example.com/foo HTTP/1.1',
                'http://example.com/foo',
                [
                    'getScheme' => 'http',
                    'getHost'   => 'example.com',
                    'getPath'   => '/foo',
                ],
            ],
            'path-and-query' => [
                'GET http://example.com/foo?bar HTTP/1.1',
                'http://example.com/foo?bar',
                [
                    'getScheme' => 'http',
                    'getHost'   => 'example.com',
                    'getPath'   => '/foo',
                    'getQuery'  => 'bar',
                ],
            ],
            'with-port'      => [
                'GET http://example.com:8080/foo?bar HTTP/1.1',
                'http://example.com:8080/foo?bar',
                [
                    'getScheme' => 'http',
                    'getHost'   => 'example.com',
                    'getPort'   => 8080,
                    'getPath'   => '/foo',
                    'getQuery'  => 'bar',
                ],
            ],
            'with-authority' => [
                'GET https://me:too@example.com:8080/foo?bar HTTP/1.1',
                'https://me:too@example.com:8080/foo?bar',
                [
                    'getScheme'   => 'https',
                    'getUserInfo' => 'me:too',
                    'getHost'     => 'example.com',
                    'getPort'     => 8080,
                    'getPath'     => '/foo',
                    'getQuery'    => 'bar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider absoluteForms
     */
    public function testCanDeserializeRequestWithAbsoluteForm($line, $requestTarget, $expectations)
    {
        $message = $line . "\r\nX-Foo-Bar: Baz\r\n\r\nContent";
        $request = Serializer::fromString($message);

        $this->assertSame('GET', $request->getMethod());

        $this->assertSame($requestTarget, $request->getRequestTarget());

        $uri = $request->getUri();
        foreach ($expectations as $method => $expect) {
            $this->assertSame($expect, $uri->{$method}());
        }
    }

    public function testCanDeserializeRequestWithAuthorityForm()
    {
        $message = "CONNECT www.example.com:80 HTTP/1.1\r\nX-Foo-Bar: Baz";
        $request = Serializer::fromString($message);
        $this->assertSame('CONNECT', $request->getMethod());
        $this->assertSame('www.example.com:80', $request->getRequestTarget());

        $uri = $request->getUri();
        $this->assertNotSame('www.example.com', $uri->getHost());
        $this->assertNotSame(80, $uri->getPort());
    }

    public function testCanDeserializeRequestWithAsteriskForm()
    {
        $message = "OPTIONS * HTTP/1.1\r\nHost: www.example.com";
        $request = Serializer::fromString($message);
        $this->assertSame('OPTIONS', $request->getMethod());
        $this->assertSame('*', $request->getRequestTarget());

        $uri = $request->getUri();
        $this->assertNotSame('www.example.com', $uri->getHost());

        $this->assertTrue($request->hasHeader('Host'));
        $this->assertSame('www.example.com', $request->getHeaderLine('Host'));
    }

    public function invalidRequestLines()
    {
        return [
            'missing-method'   => ['/foo/bar HTTP/1.1'],
            'missing-target'   => ['GET HTTP/1.1'],
            'missing-protocol' => ['GET /foo/bar'],
            'simply-malformed' => ['What is this mess?'],
        ];
    }

    /**
     * @dataProvider invalidRequestLines
     */
    public function testRaisesExceptionDuringDeserializationForInvalidRequestLine($line)
    {
        $message = $line . "\r\nX-Foo-Bar: Baz\r\n\r\nContent";

        $this->expectException(UnexpectedValueException::class);

        Serializer::fromString($message);
    }

    public function testCanDeserializeResponseWithMultipleHeadersOfSameName()
    {
        $text = "POST /foo HTTP/1.0\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\nX-Foo-Bar: Bat\r\n\r\nContent!";
        $request = Serializer::fromString($text);

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertInstanceOf(Request::class, $request);

        $this->assertTrue($request->hasHeader('X-Foo-Bar'));
        $values = $request->getHeader('X-Foo-Bar');
        $this->assertSame(['Baz', 'Bat'], $values);
    }

    public function headersWithContinuationLines()
    {
        return [
            'space' => ["POST /foo HTTP/1.0\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n Bat\r\n\r\nContent!"],
            'tab' => ["POST /foo HTTP/1.0\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n\tBat\r\n\r\nContent!"],
        ];
    }

    /**
     * @dataProvider headersWithContinuationLines
     */
    public function testCanDeserializeResponseWithHeaderContinuations($text)
    {
        $request = Serializer::fromString($text);

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertInstanceOf(Request::class, $request);

        $this->assertTrue($request->hasHeader('X-Foo-Bar'));
        $this->assertSame('Baz;Bat', $request->getHeaderLine('X-Foo-Bar'));
    }

    public function messagesWithInvalidHeaders()
    {
        return [
            'invalid-name' => [
                "GET /foo HTTP/1.1\r\nThi;-I()-Invalid: value",
                'Invalid header detected'
            ],
            'invalid-format' => [
                "POST /foo HTTP/1.1\r\nThis is not a header\r\n\r\nContent",
                'Invalid header detected'
            ],
            'invalid-continuation' => [
                "POST /foo HTTP/1.1\r\nX-Foo-Bar: Baz\r\nInvalid continuation\r\nContent",
                'Invalid header continuation'
            ],
        ];
    }

    /**
     * @dataProvider messagesWithInvalidHeaders
     */
    public function testDeserializationRaisesExceptionForMalformedHeaders($message, $exceptionMessage)
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($exceptionMessage);

        Serializer::fromString($message);
    }

    public function testFromStreamThrowsExceptionWhenStreamIsNotReadable()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(false));

        $this->expectException(InvalidArgumentException::class);

        Serializer::fromStream($stream);
    }

    public function testFromStreamThrowsExceptionWhenStreamIsNotSeekable()
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $stream
            ->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(false));

        $this->expectException(InvalidArgumentException::class);

        Serializer::fromStream($stream);
    }

    public function testFromStreamStopsReadingAfterScanningHeader()
    {
        $headers = "POST /foo HTTP/1.0\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n Bat\r\n\r\n";
        $payload = $headers . "Content!";

        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->expects($this->once())
            ->method('isReadable')
            ->will($this->returnValue(true));
        $stream
            ->expects($this->once())
            ->method('isSeekable')
            ->will($this->returnValue(true));

        // assert that full request body is not read, and returned as RelativeStream instead
        $stream->expects($this->exactly(strlen($headers)))
            ->method('read')
            ->with(1)
            ->will($this->returnCallback(function () use ($payload) {
                static $i = 0;
                return $payload[$i++];
            }));

        $stream = Serializer::fromStream($stream);

        $this->assertInstanceOf(RelativeStream::class, $stream->getBody());
    }
}
