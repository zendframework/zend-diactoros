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
        $this->message = new Request($this->stream);
    }

    public function testUsesStreamProvidedInConstructorAsBody()
    {
        $this->assertSame($this->stream, $this->message->getBody());
    }

    public function testBodyIsMutable()
    {
        $stream  = new Stream('php://memory', 'wb+');
        $this->message->setBody($stream);
        $this->assertSame($stream, $this->message->getBody());
    }

    public function testCanSetHeaders()
    {
        $headers = array(
            'Origin'        => 'http://example.com',
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer foobartoken',
        );

        $this->message->setHeaders($headers);
        $expected = array_change_key_case($headers);
        array_walk($expected, function (&$value) {
            $value = [$value];
        });
        $this->assertEquals($expected, $this->message->getHeaders());
    }

    public function testGetHeaderAsArrayReturnsHeaderValueAsArray()
    {
        $this->message->setHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertEquals(['Foo', 'Bar'], $this->message->getHeaderAsArray('X-Foo'));
    }

    public function testGetHeaderReturnsHeaderValueAsCommaConcatenatedString()
    {
        $this->message->setHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertEquals('Foo,Bar', $this->message->getHeader('X-Foo'));
    }

    public function testHasHeaderReturnsFalseIfHeaderIsNotPresent()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
    }

    public function testHasHeaderReturnsTrueIfHeaderIsPresent()
    {
        $this->message->setHeader('X-Foo', 'Foo');
        $this->assertTrue($this->message->hasHeader('X-Foo'));
    }

    public function testAddHeaderAppendsToExistingHeader()
    {
        $this->message->setHeader('X-Foo', 'Foo');
        $this->message->addHeader('X-Foo', 'Bar');
        $this->assertEquals('Foo,Bar', $this->message->getHeader('X-Foo'));
    }

    public function testAddHeadersMergesWithExistingHeaders()
    {
        $headers = [
            'Origin'        => 'http://example.com',
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer foobartoken',
        ];
        $this->message->setHeaders($headers);

        $this->message->addHeaders([
            'Accept' => 'application/*+json',
            'X-Foo'  => 'Foo',
        ]);

        $this->assertEquals(['application/json', 'application/*+json'], $this->message->getHeaderAsArray('accept'));
        $this->assertEquals('Foo', $this->message->getHeader('x-foo'));
    }

    public function testCanRemoveHeaders()
    {
        $this->message->setHeader('X-Foo', 'Foo');
        $this->assertTrue($this->message->hasHeader('x-foo'));
        $this->message->removeHeader('x-foo');
        $this->assertFalse($this->message->hasHeader('X-Foo'));
    }

    public function invalidGeneralHeaderValues()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [[ 'foo' => 'bar' ]],
            'object' => [(object) [ 'foo' => 'bar' ]],
        ];
    }

    /**
     * @dataProvider invalidGeneralHeaderValues
     */
    public function testSetHeaderRaisesExceptionForInvalidNestedHeaderValue($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid header value');
        $this->message->setHeader('X-Foo', [ $value ]);
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
        $this->message->setHeader('X-Foo', $value);
    }

    public function testSetHeadersRaisesExceptionForNonStringKeys()
    {
        $this->setExpectedException('InvalidArgumentException', 'not a string');
        $this->message->setHeaders([
            'application/json',
            'text/plain',
        ]);
    }

    /**
     * @dataProvider invalidGeneralHeaderValues
     */
    public function testAddHeaderRaisesExceptionForNonStringValue($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'must be a string');
        $this->message->addHeader('X-Foo', $value);
    }

    public function testRemoveHeaderDoesNothingIfHeaderDoesNotExist()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
        $this->message->removeHeader('X-Foo');
        $this->assertFalse($this->message->hasHeader('X-Foo'));
    }

    public function testAllowAddingHeaderValuesUsingObjectsThatCastToString()
    {
        $header = new TestAsset\Header();
        $header->value = 'foo; bar; baz, bat';
        $this->message->addHeader('X-Foo', $header);
        $this->assertEquals((string) $header, $this->message->getHeader('X-Foo'));
    }

    public function testAllowSettingArrayOfHeaderValuesUsingObjectsThatCastToString()
    {
        $values = [];
        foreach (range(1, 5) as $i) {
            $header = new TestAsset\Header();
            $header->value = 'foo(' . $i . ')';
            $values[] = $header;
        }
        $this->message->setHeader('X-Foo', $values);

        $value = $this->message->getHeader('X-Foo');
        foreach (range(1, 5) as $i) {
            $this->assertContains('foo(' . $i . ')', $value);
        }
    }
}
