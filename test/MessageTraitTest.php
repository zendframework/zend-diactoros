<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Request;

use function count;

class MessageTraitTest extends TestCase
{
    /**
     * @var MessageInterface
     */
    protected $message;

    public function setUp()
    {
        $this->message = new Request(null, null, $this->createMock(StreamInterface::class));
    }

    public function testProtocolHasAcceptableDefault()
    {
        $this->assertSame('1.1', $this->message->getProtocolVersion());
    }

    public function testProtocolMutatorReturnsCloneWithChanges()
    {
        $message = $this->message->withProtocolVersion('1.0');
        $this->assertNotSame($this->message, $message);
        $this->assertSame('1.0', $message->getProtocolVersion());
    }


    public function invalidProtocolVersionProvider()
    {
        return [
            'null'                 => [ null ],
            'true'                 => [ true ],
            'false'                => [ false ],
            'int'                  => [ 1 ],
            'float'                => [ 1.1 ],
            'array'                => [ ['1.1'] ],
            'stdClass'             => [ (object) [ 'version' => '1.0'] ],
            '1-without-minor'      => [ '1' ],
            '1-with-invalid-minor' => [ '1.2' ],
            '1-with-hotfix'        => [ '1.2.3' ],
            '2-with-minor'         => [ '2.0' ],
        ];
    }

    /**
     * @dataProvider invalidProtocolVersionProvider
     */
    public function testWithProtocolVersionRaisesExceptionForInvalidVersion($version)
    {
        $request = new Request();

        $this->expectException(InvalidArgumentException::class);

        $request->withProtocolVersion($version);
    }

    public function testUsesStreamProvidedInConstructorAsBody()
    {
        $stream  = $this->createMock(StreamInterface::class);
        $message = new Request(null, null, $stream);
        $this->assertSame($stream, $message->getBody());
    }

    public function testBodyMutatorReturnsCloneWithChanges()
    {
        $stream  = $this->createMock(StreamInterface::class);
        $message = $this->message->withBody($stream);
        $this->assertNotSame($this->message, $message);
        $this->assertSame($stream, $message->getBody());
    }

    public function testGetHeaderReturnsHeaderValueAsArray()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['Foo', 'Bar'], $message->getHeader('X-Foo'));
    }

    public function testGetHeaderLineReturnsHeaderValueAsCommaConcatenatedString()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame('Foo,Bar', $message->getHeaderLine('X-Foo'));
    }

    public function testGetHeadersKeepsHeaderCaseSensitivity()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame([ 'X-Foo' => [ 'Foo', 'Bar' ] ], $message->getHeaders());
    }

    public function testGetHeadersReturnsCaseWithWhichHeaderFirstRegistered()
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar');
        $this->assertNotSame($this->message, $message);
        $this->assertSame([ 'X-Foo' => [ 'Foo', 'Bar' ] ], $message->getHeaders());
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
        $this->assertSame('Foo,Bar', $message2->getHeaderLine('X-Foo'));
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
        $this->assertSame(0, count($headers));
    }

    public function invalidGeneralHeaderValues()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'array'  => [[ 'foo' => [ 'bar' ] ]],
            'object' => [(object) [ 'foo' => 'bar' ]],
        ];
    }

    /**
     * @dataProvider invalidGeneralHeaderValues
     */
    public function testWithHeaderRaisesExceptionForInvalidNestedHeaderValue($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header value');

        $this->message->withHeader('X-Foo', [ $value ]);
    }

    public function invalidHeaderValues()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'object' => [(object) [ 'foo' => 'bar' ]],
        ];
    }

    /**
     * @dataProvider invalidHeaderValues
     */
    public function testWithHeaderRaisesExceptionForInvalidValueType($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header value');

        $this->message->withHeader('X-Foo', $value);
    }

    public function testWithHeaderReplacesDifferentCapitalization()
    {
        $this->message = $this->message->withHeader('X-Foo', ['foo']);
        $new = $this->message->withHeader('X-foo', ['bar']);
        $this->assertSame(['bar'], $new->getHeader('x-foo'));
        $this->assertSame(['X-foo' => ['bar']], $new->getHeaders());
    }

    /**
     * @dataProvider invalidGeneralHeaderValues
     */
    public function testWithAddedHeaderRaisesExceptionForNonStringNonArrayValue($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a string');

        $this->message->withAddedHeader('X-Foo', $value);
    }

    public function testWithoutHeaderDoesNothingIfHeaderDoesNotExist()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
        $message = $this->message->withoutHeader('X-Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertFalse($message->hasHeader('X-Foo'));
    }

    public function testHeadersInitialization()
    {
        $headers = ['X-Foo' => ['bar']];
        $message = new Request(null, null, 'php://temp', $headers);
        $this->assertSame($headers, $message->getHeaders());
    }

    public function testGetHeaderReturnsAnEmptyArrayWhenHeaderDoesNotExist()
    {
        $this->assertSame([], $this->message->getHeader('X-Foo-Bar'));
    }

    public function testGetHeaderLineReturnsEmptyStringWhenHeaderDoesNotExist()
    {
        $this->assertEmpty($this->message->getHeaderLine('X-Foo-Bar'));
    }

    public function headersWithInjectionVectors()
    {
        return [
            'name-with-cr'           => ["X-Foo\r-Bar", 'value'],
            'name-with-lf'           => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf'         => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf'        => ["X-Foo\r\n\r\n-Bar", 'value'],
            'value-with-cr'          => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf'          => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf'        => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf'       => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr'    => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf'    => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf'  => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf' => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
        ];
    }

    /**
     * @dataProvider headersWithInjectionVectors
     * @group ZF2015-04
     */
    public function testDoesNotAllowCRLFInjectionWhenCallingWithHeader($name, $value)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->message->withHeader($name, $value);
    }

    /**
     * @dataProvider headersWithInjectionVectors
     * @group ZF2015-04
     */
    public function testDoesNotAllowCRLFInjectionWhenCallingWithAddedHeader($name, $value)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->message->withAddedHeader($name, $value);
    }

    public function testWithHeaderAllowsHeaderContinuations()
    {
        $message = $this->message->withHeader('X-Foo-Bar', "value,\r\n second value");
        $this->assertSame("value,\r\n second value", $message->getHeaderLine('X-Foo-Bar'));
    }

    public function testWithAddedHeaderAllowsHeaderContinuations()
    {
        $message = $this->message->withAddedHeader('X-Foo-Bar', "value,\r\n second value");
        $this->assertSame("value,\r\n second value", $message->getHeaderLine('X-Foo-Bar'));
    }

    public function numericHeaderValuesProvider()
    {
        return [
            'integer' => [ 123 ],
            'float'   => [ 12.3 ],
        ];
    }

    /**
     * @dataProvider numericHeaderValuesProvider
     * @group 99
     */
    public function testWithHeaderShouldAllowIntegersAndFloats($value)
    {
        $message = $this->message
            ->withHeader('X-Test-Array', [ $value ])
            ->withHeader('X-Test-Scalar', $value);

        $this->assertSame([
            'X-Test-Array'  => [ (string) $value ],
            'X-Test-Scalar' => [ (string) $value ],
        ], $message->getHeaders());
    }

    public function invalidHeaderValueTypes()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'object' => [(object) ['header' => ['foo', 'bar']]],
        ];
    }

    public function invalidArrayHeaderValues()
    {
        $values = $this->invalidHeaderValueTypes();
        $values['array'] = [['INVALID']];
        return $values;
    }

    /**
     * @dataProvider invalidArrayHeaderValues
     * @group 99
     */
    public function testWithHeaderShouldRaiseExceptionForInvalidHeaderValuesInArrays($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('header value type');

        $this->message->withHeader('X-Test-Array', [ $value ]);
    }

    /**
     * @dataProvider invalidHeaderValueTypes
     * @group 99
     */
    public function testWithHeaderShouldRaiseExceptionForInvalidHeaderScalarValues($value)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('header value type');

        $this->message->withHeader('X-Test-Scalar', $value);
    }
}
