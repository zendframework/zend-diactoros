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

    public function testMethodMutatorReturnsCloneWithChangedMethod()
    {
        $request = $this->request->setMethod('GET');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals('GET', $request->getMethod());
    }

    public function testUrlIsNullByDefault()
    {
        $this->assertNull($this->request->getUrl());
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

    /**
     * @dataProvider invalidUrls
     */
    public function testCannotSetUrlWithInvalidType($url)
    {
        $this->setExpectedException('InvalidArgumentException', 'must be');
        $request = $this->request->setUrl($url);
    }

    public function testAbsoluteUriIsNullByDefault()
    {
        $this->assertNull($this->request->getAbsoluteUri());
    }

    /**
     * @dataProvider invalidUrls
     */
    public function testCannotSetAbsoluteUriWithInvalidType($uri)
    {
        $this->setExpectedException('InvalidArgumentException', 'must be');
        $request = $this->request->setAbsoluteUri($uri);
    }

    public function invalidAbsoluteUris()
    {
        return [
            'empty'                => [''],
            'query-only'           => ['?foo=bar'],
            'path-only'            => ['/foo'],
            'path+query'           => ['/foo?bar=baz'],
            'host+path+query'      => ['//foo.com/foo?bar=baz'],
            'host+port+path+query' => ['//foo.com:8080/foo?bar=baz'],
            'scheme-only'          => ['http://'],
        ];
    }

    /**
     * @dataProvider invalidAbsoluteUris
     */
    public function testCannotSetAbsoluteUriWithMissingRequiredInformation($uri)
    {
        $this->setExpectedException('InvalidArgumentException');
        $request = $this->request->setAbsoluteUri($uri);
    }

    public function testSettingUrlUpdatesAbsoluteUri()
    {
        $request = $this->request->setAbsoluteUri('https://example.com:10082/foo/bar?baz=bat');
        $this->assertNotSame($this->request, $request);
        $request2 = $request->setUrl('/baz/bat?foo=bar');
        $this->assertNotSame($this->request, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertEquals('https://example.com:10082/baz/bat?foo=bar', $request2->getAbsoluteUri());
    }

    public function testSettingAbsoluteUriSetsUrl()
    {
        $request = $this->request->setUrl('/baz/bat?foo=bar');
        $this->assertNotSame($this->request, $request);
        $request2 = $request->setAbsoluteUri('https://example.com:10082/foo/bar?baz=bat');
        $this->assertNotSame($this->request, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertEquals('/foo/bar?baz=bat', $request2->getUrl());
    }

    public function testSettingEmptyUrlClearsAbsoluteUriPath()
    {
        $request = $this->request->setAbsoluteUri('https://example.com:10082/foo/bar?baz=bat');
        $request = $request->setUrl('');
        $this->assertEquals('https://example.com:10082/', $request->getAbsoluteUri());
    }

    public function testSettingUrlToQueryStringOnlyClearsAbsoluteUriPathAndSetsQueryString()
    {
        $request = $this->request->setAbsoluteUri('https://example.com:10082/foo/bar?baz=bat');
        $request = $request->setUrl('?foo=bar');
        $this->assertEquals('https://example.com:10082/?foo=bar', $request->getAbsoluteUri());
    }

    public function testSettingUrlToPathResetsAbsoluteUriPathAndClearsAbsoluteUriQueryString()
    {
        $request = $this->request->setAbsoluteUri('https://example.com:10082/foo/bar?baz=bat');
        $request = $request->setUrl('/bar/baz');
        $this->assertEquals('https://example.com:10082/bar/baz', $request->getAbsoluteUri());
    }
}
