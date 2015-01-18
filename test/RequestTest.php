<?php
namespace PhlyTest\Http;

use Phly\Http\Request;
use Phly\Http\Stream;
use Phly\Http\Uri;
use PHPUnit_Framework_TestCase as TestCase;

class RequestTest extends TestCase
{
    public function setUp()
    {
        $this->request = new Request();
    }

    public function testMethodIsNullByDefault()
    {
        $this->assertNull($this->request->getMethod());
    }

    public function testMethodMutatorReturnsCloneWithChangedMethod()
    {
        $request = $this->request->withMethod('GET');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals('GET', $request->getMethod());
    }

    public function testUriIsNullByDefault()
    {
        $this->assertNull($this->request->getUri());
    }

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Request(['TOTALLY INVALID']);
    }

    public function invalidUrls()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['foo']],
            'object' => [(object) ['foo']],
        ];
    }

    public function testWithUriReturnsNewInstanceWithNewUri()
    {
        $request = $this->request->withUri(new Uri('https://example.com:10082/foo/bar?baz=bat'));
        $this->assertNotSame($this->request, $request);
        $request2 = $request->withUri(new Uri('/baz/bat?foo=bar'));
        $this->assertNotSame($this->request, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertEquals('/baz/bat?foo=bar', (string) $request2->getUri());
    }

    public function testConstructorCanAcceptAllMessageParts()
    {
        $uri     = new Uri('http://example.com/');
        $body    = new Stream('php://memory');
        $headers = [
            'x-foo' => ['bar'],
        ];
        $request = new Request(
            $uri,
            'POST',
            $body,
            $headers
        );

        $this->assertSame($uri, $request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertSame($body, $request->getBody());
        $this->assertEquals($headers, $request->getHeaders());
    }

    public function invalidRequestUri()
    {
        return [
            'true'     => [ true ],
            'false'    => [ false ],
            'int'      => [ 1 ],
            'float'    => [ 1.1 ],
            'array'    => [ ['http://example.com'] ],
            'stdClass' => [ (object) [ 'href'         => 'http://example.com'] ],
        ];
    }

    /**
     * @dataProvider invalidRequestUri
     */
    public function testConstructorRaisesExceptionForInvalidUri($uri)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid URI');
        new Request($uri);
    }

    public function invalidRequestMethod()
    {
        return [
            'true'       => [ true ],
            'false'      => [ false ],
            'int'        => [ 1 ],
            'float'      => [ 1.1 ],
            'bad-string' => [ 'BOGUS-METHOD' ],
            'array'      => [ ['POST'] ],
            'stdClass'   => [ (object) [ 'method' => 'POST'] ],
        ];
    }

    /**
     * @dataProvider invalidRequestMethod
     */
    public function testConstructorRaisesExceptionForInvalidMethod($method)
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported HTTP method');
        new Request(null, $method);
    }

    public function invalidRequestBody()
    {
        return [
            'true'       => [ true ],
            'false'      => [ false ],
            'int'        => [ 1 ],
            'float'      => [ 1.1 ],
            'array'      => [ ['BODY'] ],
            'stdClass'   => [ (object) [ 'body' => 'BODY'] ],
        ];
    }

    /**
     * @dataProvider invalidRequestBody
     */
    public function testConstructorRaisesExceptionForInvalidBody($body)
    {
        $this->setExpectedException('InvalidArgumentException', 'stream');
        new Request(null, null, $body);
    }

    public function testConstructorIgonoresInvalidHeaders()
    {
        $headers = [
            [ 'INVALID' ],
            'x-invalid-null' => null,
            'x-invalid-true' => true,
            'x-invalid-false' => false,
            'x-invalid-int' => 1,
            'x-invalid-object' => (object) ['INVALID'],
            'x-valid-string' => 'VALID',
            'x-valid-array' => [ 'VALID' ],
        ];
        $expected = [
            'x-valid-string' => [ 'VALID' ],
            'x-valid-array' => [ 'VALID' ],
        ];
        $request = new Request(null, null, 'php://memory', $headers);
        $this->assertEquals($expected, $request->getHeaders());
    }
}
