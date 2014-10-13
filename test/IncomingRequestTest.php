<?php
namespace PhlyTest\Http;

use Phly\Http\IncomingRequest;
use PHPUnit_Framework_TestCase as TestCase;

class IncomingRequestTest extends TestCase
{
    public function setUp()
    {
        $this->request = new IncomingRequest();
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

    public function testPathParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getPathParams());
    }

    /**
     * @depends testCookiesAreEmptyByDefault
     */
    public function testCookieParamsAreMutable()
    {
        $cookies = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];
        $this->request->setCookieParams($cookies);
        $this->assertEquals($cookies, $this->request->getCookieParams());
    }

    /**
     * @depends testBodyParamsAreEmptyByDefault
     */
    public function testBodyParamsAreMutable()
    {
        $params = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];
        $this->request->setBodyParams($params);
        $this->assertEquals($params, $this->request->getBodyParams());
    }

    /**
     * @depends testPathParamsAreEmptyByDefault
     */
    public function testPathParamsAreMutable()
    {
        $params = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];
        $this->request->setPathParams($params);
        $this->assertEquals($params, $this->request->getPathParams());
    }

    public function testRequestDataMayBeSetAsDiscreteConstructorArguments()
    {
        $cookies = $path = $query = $body = $files = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $cookies['cookies'] = true;
        $path['path']       = true;
        $query['query']     = true;
        $body['body']       = true;
        $files['files']     = true;

        $request = new IncomingRequest('php://memory', $cookies, $path, $query, $body, $files);

        $this->assertEquals($cookies, $request->getCookieParams());
        $this->assertEquals($path, $request->getPathParams());
        $this->assertEquals($query, $request->getQueryParams());
        $this->assertEquals($body, $request->getBodyParams());
        $this->assertEquals($files, $request->getFileParams());
    }

    public function testRequestDataMayBePassedViaAnAssociativeArray()
    {
        $cookies = $path = $query = $body = $files = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $cookies['cookies'] = true;
        $path['path']       = true;
        $query['query']     = true;
        $body['body']       = true;
        $files['files']     = true;

        $data = [
            'stream'       => 'php://memory',
            'cookieParams' => $cookies,
            'pathParams'   => $path,
            'queryParams'  => $query,
            'bodyParams'   => $body,
            'fileParams'   => $files,
        ];

        $request = new IncomingRequest($data);

        $this->assertEquals($cookies, $request->getCookieParams());
        $this->assertEquals($path, $request->getPathParams());
        $this->assertEquals($query, $request->getQueryParams());
        $this->assertEquals($body, $request->getBodyParams());
        $this->assertEquals($files, $request->getFileParams());
    }
}
