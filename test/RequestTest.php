<?php
namespace PhlyTest\Http;

use Phly\Http\Request;
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

    public function testMethodIsMutable()
    {
        $this->request->setMethod('GET');
        $this->assertEquals('GET', $this->request->getMethod());
    }

    public function testUrlIsNullByDefault()
    {
        $this->assertNull($this->request->getUrl());
    }

    public function testSetUrlCastsStringsToUriObjects()
    {
        $url = 'http://test.example.com/foo';
        $this->request->setUrl($url);
        $uri = $this->request->getUrl();
        $this->assertInstanceOf('Phly\Http\Uri', $uri);
        $this->assertEquals($url, $uri->uri);
    }

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Request('1.1', ['TOTALLY INVALID']);
    }

    public function testCannotOverrideMethodOnceSet()
    {
        $this->request->setMethod('POST');
        $this->setExpectedException('RuntimeException');
        $this->request->setMethod('PATCH');
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

    /**
     * @dataProvider invalidUrls
     */
    public function testCannotSetUrlWithInvalidType($url)
    {
        $this->setExpectedException('InvalidArgumentException', 'must be');
        $this->request->setUrl($url);
    }

    public function testCannotSetUrlIfInvalid()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid URL provided');
        $this->request->setUrl('foo');
    }
}
