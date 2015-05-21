<?php
namespace PhlyTest\Http\Response;

use Phly\Http\Response;
use Phly\Http\Response\Serializer;
use Phly\Http\Stream;
use PHPUnit_Framework_TestCase as TestCase;

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
        $this->assertEquals(
            "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\n\r\nContent!",
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

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertInstanceOf('Phly\Http\Response', $response);

        $this->assertEquals('1.0', $response->getProtocolVersion());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('A-OK', $response->getReasonPhrase());

        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertEquals('text/plain', $response->getHeaderLine('Content-Type'));

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $this->assertEquals('Baz', $response->getHeaderLine('X-Foo-Bar'));

        $this->assertEquals('Content!', (string) $response->getBody());
    }

    public function testCanDeserializeResponseWithMultipleHeadersOfSameName()
    {
        $text = "HTTP/1.0 200 A-OK\r\nContent-Type: text/plain\r\nX-Foo-Bar: Baz\r\nX-Foo-Bar: Bat\r\n\r\nContent!";
        $response = Serializer::fromString($text);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertInstanceOf('Phly\Http\Response', $response);

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $values = $response->getHeader('X-Foo-Bar');
        $this->assertEquals(['Baz', 'Bat'], $values);
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

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertInstanceOf('Phly\Http\Response', $response);

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $this->assertEquals('Baz;Bat', $response->getHeaderLine('X-Foo-Bar'));
    }

    public function testCanDeserializeResponseWithoutBody()
    {
        $text = "HTTP/1.0 204\r\nX-Foo-Bar: Baz";
        $response = Serializer::fromString($text);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertInstanceOf('Phly\Http\Response', $response);

        $this->assertTrue($response->hasHeader('X-Foo-Bar'));
        $this->assertEquals('Baz', $response->getHeaderLine('X-Foo-Bar'));

        $body = $response->getBody()->getContents();
        $this->assertEmpty($body);
    }

    public function testCanDeserializeResponseWithoutHeadersOrBody()
    {
        $text = "HTTP/1.0 204";
        $response = Serializer::fromString($text);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertInstanceOf('Phly\Http\Response', $response);

        $this->assertEmpty($response->getHeaders());
        $body = $response->getBody()->getContents();
        $this->assertEmpty($body);
    }

    public function testCanDeserializeResponseWithoutHeadersButContainingBody()
    {
        $text = "HTTP/1.0 204\r\n\r\nContent!";
        $response = Serializer::fromString($text);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertInstanceOf('Phly\Http\Response', $response);

        $this->assertEmpty($response->getHeaders());
        $body = $response->getBody()->getContents();
        $this->assertEquals('Content!', $body);
    }

    public function testDeserializationRaisesExceptionForInvalidStatusLine()
    {
        $text = "This is an invalid status line\r\nX-Foo-Bar: Baz\r\n\r\nContent!";
        $this->setExpectedException('UnexpectedValueException', 'status line');
        $response = Serializer::fromString($text);
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
        $this->setExpectedException('UnexpectedValueException', $exceptionMessage);
        $response = Serializer::fromString($message);
    }

    public function testFromStreamThrowsExceptionWhenStreamIsNotReadable()
    {
        $this->setExpectedException('InvalidArgumentException');
        $stream = $this
            ->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->getMock();

        $stream->expects($this->once())->method('isReadable')
            ->will($this->returnValue(false));

        Serializer::fromStream($stream);
    }

    public function testFromStreamThrowsExceptionWhenStreamIsNotSeekable()
    {
        $this->setExpectedException('InvalidArgumentException');
        $stream = $this
            ->getMockBuilder('Psr\Http\Message\StreamInterface')
            ->getMock();

        $stream->expects($this->once())->method('isReadable')
            ->will($this->returnValue(true));

        $stream->expects($this->once())->method('isSeekable')
            ->will($this->returnValue(false));

        Serializer::fromStream($stream);
    }
}
