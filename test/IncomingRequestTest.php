<?php
namespace PhlyTest\Http;

use Phly\Http\IncomingRequest;
use PHPUnit_Framework_TestCase as TestCase;

class IncomingRequestTest extends TestCase
{
    public function setUp()
    {
        $this->request = new IncomingRequest('http://example.com/');
    }

    public function testServerParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getServerParams());
    }

    public function testQueryParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getQueryParams());
    }

    public function testCookiesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getCookieParams());
    }

    public function testFileParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getFileParams());
    }

    public function testBodyParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getBodyParams());
    }

    public function testAttributesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getAttributes());
    }

    /**
     * @depends testAttributesAreEmptyByDefault
     */
    public function testAttributesAreMutable()
    {
        $params = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];
        $this->request->setAttributes($params);
        $this->assertEquals($params, $this->request->getAttributes());
    }

    public function testRequestDataMayBeSetAsDiscreteConstructorArguments()
    {
        $server = $cookies = $attributes = $query = $body = $files = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $server['server']   = true;
        $cookies['cookies'] = true;
        $attributes['path'] = true;
        $query['query']     = true;
        $body['body']       = true;
        $files['files']     = true;

        $headers = [
            'X-Foo' => 'bar',
            'X-Bar' => ['baz', 'bat'],
        ];

        $request = new IncomingRequest(
            'http://example.com/',
            'post',
            $headers,
            'php://memory',
            $server,
            $cookies,
            $query,
            $body,
            $files,
            $attributes,
            '1.0'
        );

        $this->assertEquals('http://example.com/', $request->getUrl());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals([
            'x-foo' => ['bar'],
            'x-bar' => ['baz', 'bat'],
        ], $request->getHeaders());

        $this->assertEquals($server, $request->getServerParams());
        $this->assertEquals($cookies, $request->getCookieParams());
        $this->assertEquals($query, $request->getQueryParams());
        $this->assertEquals($body, $request->getBodyParams());
        $this->assertEquals($files, $request->getFileParams());
        $this->assertEquals($attributes, $request->getAttributes());
        $this->assertEquals('1.0', $request->getProtocolVersion());
    }

    public function testRequestDataMayBePassedViaAnAssociativeArray()
    {
        $server = $cookies = $attributes = $query = $body = $files = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $server['server']   = true;
        $cookies['cookies'] = true;
        $attributes['path'] = true;
        $query['query']     = true;
        $body['body']       = true;
        $files['files']     = true;

        $headers = [
            'X-Foo' => 'bar',
            'X-Bar' => ['baz', 'bat'],
        ];

        $data = [
            'url'          => 'http://example.com/',
            'method'       => 'post',
            'headers'      => $headers,
            'stream'       => 'php://memory',
            'server'       => $server,
            'cookie'       => $cookies,
            'query'        => $query,
            'body'         => $body,
            'file'         => $files,
            'attributes'   => $attributes,
            'protocol'     => '1.0',
        ];

        $request = new IncomingRequest($data);

        $this->assertEquals('http://example.com/', $request->getUrl());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals([
            'x-foo' => ['bar'],
            'x-bar' => ['baz', 'bat'],
        ], $request->getHeaders());

        $this->assertEquals($server, $request->getServerParams());
        $this->assertEquals($cookies, $request->getCookieParams());
        $this->assertEquals($query, $request->getQueryParams());
        $this->assertEquals($body, $request->getBodyParams());
        $this->assertEquals($files, $request->getFileParams());
        $this->assertEquals($attributes, $request->getAttributes());
        $this->assertEquals('1.0', $request->getProtocolVersion());
    }

    public function testGetUrlIssue19()
    {
        $request = new IncomingRequest('http://example.com/');
        $this->assertInstanceOf('Phly\Http\Uri', $request->getUrl());
    }
}
