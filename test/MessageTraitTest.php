<?php
namespace PhlyTest\Http;

use Phly\Http\Request;
use Phly\Http\Stream;
use PHPUnit_Framework_TestCase as TestCase;

class MessageTraitTest extends TestCase
{
    public function setUp()
    {
        $this->stream  = new Stream('php://memory', 'wb+');
        $this->message = new Request(null, null, $this->stream);
    }

    public function testProtocolHasAcceptableDefault()
    {
        $this->assertEquals('1.1', $this->message->getProtocolVersion());
    }

    public function testProtocolMutatorReturnsCloneWithChanges()
    {
        $message = $this->message->withProtocolVersion('1.0');
        $this->assertNotSame($this->message, $message);
        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

    public function testUsesStreamProvidedInConstructorAsBody()
    {
        $this->assertSame($this->stream, $this->message->getBody());
    }

    public function testBodyMutatorReturnsCloneWithChanges()
    {
        $stream  = new Stream('php://memory', 'wb+');
        $message = $this->message->withBody($stream);
        $this->assertNotSame($this->message, $message);
        $this->assertSame($stream, $message->getBody());
    }

    public function testGetHeaderLinesReturnsHeaderValueAsArray()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertEquals(['Foo', 'Bar'], $message->getHeaderLines('X-Foo'));
    }

    public function testGetHeaderReturnsHeaderValueAsCommaConcatenatedString()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertEquals('Foo,Bar', $message->getHeader('X-Foo'));
    }

    public function testGetHeadersKeepsHeaderCaseSensitivity()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertEquals([ 'X-Foo' => [ 'Foo', 'Bar' ] ], $message->getHeaders());
    }

    public function testGetHeadersReturnsCaseWithWhichHeaderFirstRegistered()
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar');
        $this->assertNotSame($this->message, $message);
        $this->assertEquals([ 'X-Foo' => [ 'Foo', 'Bar' ] ], $message->getHeaders());
    }

    public function testHasHeaderReturnsFalseIfHeaderIsNotPresent()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
    }

    public function testHasHeaderReturnsTrueIfHeaderIsPresent()
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('X-Foo'));
    }

    public function testAddHeaderAppendsToExistingHeader()
    {
        $message  = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $message2 = $message->withAddedHeader('X-Foo', 'Bar');
        $this->assertNotSame($message, $message2);
        $this->assertEquals('Foo,Bar', $message2->getHeader('X-Foo'));
    }

    public function testCanRemoveHeaders()
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));
        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));
    }

    public function testHeaderRemovalIsCaseInsensitive()
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar')
            ->withAddedHeader('X-FOO', 'Baz');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));

        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));

        $headers = $message2->getHeaders();
        $this->assertEquals(0, count($headers));
    }

    public function invalidGeneralHeaderValues()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [[ 'foo' => [ 'bar' ] ]],
            'object' => [(object) [ 'foo' => 'bar' ]],
        ];
    }

    /**
     * @dataProvider invalidGeneralHeaderValues
     */
    public function testSetHeaderRaisesExceptionForInvalidNestedHeaderValue($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid header value');
        $message = $this->message->withHeader('X-Foo', [ $value ]);
    }

    public function invalidHeaderValues()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'object' => [(object) [ 'foo' => 'bar' ]],
        ];
    }

    /**
     * @dataProvider invalidHeaderValues
     */
    public function testSetHeaderRaisesExceptionForInvalidValueType($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid header value');
        $message = $this->message->withHeader('X-Foo', $value);
    }

    /**
     * @dataProvider invalidGeneralHeaderValues
     */
    public function testAddHeaderRaisesExceptionForNonStringNonArrayValue($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'must be a string');
        $message = $this->message->withAddedHeader('X-Foo', $value);
    }

    public function testRemoveHeaderDoesNothingIfHeaderDoesNotExist()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
        $message = $this->message->withoutHeader('X-Foo');
        $this->assertSame($this->message, $message);
        $this->assertFalse($message->hasHeader('X-Foo'));
    }

    public function testHeadersInitialization()
    {
        $headers = ['X-Foo' => ['bar']];
        $this->message = new Request(null, null, $this->stream, $headers);
        $this->assertSame($headers, $this->message->getHeaders());
    }
}
