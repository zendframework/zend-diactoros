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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use UnexpectedValueException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\Serializer;

class SerializerTest extends TestCase
{
    public function testSerializesBasicResponse()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain')
            ->withAddedHeader('X-Foo-Bar', 'Baz');
        $response->getBody()->write('Content!');

        $message = Serializer::toString($response);
        $this->assertSame(
            "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\n\r\nContent!",
            $message
        );
    }

    public function testSerializesResponseWithoutBodyCorrectly()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');

        $message = Serializer::toString($response);
        $this->assertSame(
            "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\n",
            $message
        );
    }

    public function testSerializesMultipleHeadersCorrectly()
    {
        $response = (new Response())
            ->withStatus(204)
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat');

        $message = Serializer::toString($response);
        $this->assertContains("X-Foo-Bar: Baz", $message);
        $this->assertContains("X-Foo-Bar: Bat", $message);
    }

    public function testOmitsReasonPhraseFromStatusLineIfEmpty()
    {
        $response = (new Response())
            ->withStatus(299)
            ->withAddedHeader('X-Foo-Bar', 'Baz');
        $response->getBody()->write('Content!');

        $message = Serializer::toString($response);
        $this->assertContains("HTTP/1.1 299\r\n", $message);
    }

    public function testCanDeserializeBasicResponse()
    {
        $text = "HTTP/1.0 200 A-OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\n\r\nContent!";
        $response = Serializer::fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertSame('1.0', $response->getProtocolVersion());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('A-OK', $response->getReasonPhrase());

        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $this->assertSame('Baz', $response->getHeaderLine('X-Foo-Bar'));

        $this->assertSame('Content!', (string) $response->getBody());
    }

    public function testCanDeserializeResponseWithMultipleHeadersOfSameName()
    {
        $text = "HTTP/1.0 200 A-OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\nX-Foo-Bar: Bat\r\n\r\nContent!";
        $response = Serializer::fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $values = $response->getHeader('X-Foo-Bar');
        $this->assertSame(['Baz', 'Bat'], $values);
    }

    public function headersWithContinuationLines()
    {
        return [
            'space' => ["HTTP/1.0 200 A-OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n Bat\r\n\r\nContent!"],
            'tab' => ["HTTP/1.0 200 A-OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz;\r\n\tBat\r\n\r\nContent!"],
        ];
    }

    /**
     * @dataProvider headersWithContinuationLines
     */
    public function testCanDeserializeResponseWithHeaderContinuations($text)
    {
        $response = Serializer::fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $this->assertSame('Baz;Bat', $response->getHeaderLine('X-Foo-Bar'));
    }

    public function testCanDeserializeResponseWithoutBody()
    {
        $text = "HTTP/1.0 204\r\nX-Foo-Bar: Baz";
        $response = Serializer::fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $this->assertSame('Baz', $response->getHeaderLine('X-Foo-Bar'));

        $body = $response->getBody()->getContents();
        $this->assertEmpty($body);
    }

    public function testCanDeserializeResponseWithoutHeadersOrBody()
    {
        $text = "HTTP/1.0 204";
        $response = Serializer::fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEmpty($response->getHeaders());
        $body = $response->getBody()->getContents();
        $this->assertEmpty($body);
    }

    public function testCanDeserializeResponseWithoutHeadersButContainingBody()
    {
        $text = "HTTP/1.0 204\r\n\r\nContent!";
        $response = Serializer::fromString($text);

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEmpty($response->getHeaders());
        $body = $response->getBody()->getContents();
        $this->assertSame('Content!', $body);
    }

    public function testDeserializationRaisesExceptionForInvalidStatusLine()
    {
        $text = "This is an invalid status line\r\nX-Foo-Bar: Baz\r\n\r\nContent!";

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('status line');

        Serializer::fromString($text);
    }

    public function messagesWithInvalidHeaders()
    {
        return [
            'invalid-name' => [
                "HTTP/1.1 204\r\nThi;-I()-Invalid: value",
                'Invalid header detected'
            ],
            'invalid-format' => [
                "HTTP/1.1 204\r\nThis is not a header\r\n\r\nContent",
                'Invalid header detected'
            ],
            'invalid-continuation' => [
                "HTTP/1.1 204\r\nX-Foo-Bar: Baz\r\nInvalid continuation\r\nContent",
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

    /**
     * @group 113
     */
    public function testDeserializeCorrectlyCastsStatusCodeToInteger()
    {
        $response = Response\Serializer::fromString('HTTP/1.0 204');
        // according to interface the int is expected
        $this->assertSame(204, $response->getStatusCode());
    }
}
