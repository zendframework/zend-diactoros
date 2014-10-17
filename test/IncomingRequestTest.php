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

    public function testAttributesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getAttributes());
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
        $cookies = $attributes = $query = $body = $files = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $cookies['cookies'] = true;
        $attributes['path'] = true;
        $query['query']     = true;
        $body['body']       = true;
        $files['files']     = true;

        $request = new IncomingRequest('php://memory', $cookies, $attributes, $query, $body, $files);

        $this->assertEquals($cookies, $request->getCookieParams());
        $this->assertEquals($attributes, $request->getAttributes());
        $this->assertEquals($query, $request->getQueryParams());
        $this->assertEquals($body, $request->getBodyParams());
        $this->assertEquals($files, $request->getFileParams());
    }

    public function testRequestDataMayBePassedViaAnAssociativeArray()
    {
        $cookies = $attributes = $query = $body = $files = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $cookies['cookies'] = true;
        $attributes['path'] = true;
        $query['query']     = true;
        $body['body']       = true;
        $files['files']     = true;

        $data = [
            'stream'       => 'php://memory',
            'cookieParams' => $cookies,
            'attributes'   => $attributes,
            'queryParams'  => $query,
            'bodyParams'   => $body,
            'fileParams'   => $files,
        ];

        $request = new IncomingRequest($data);

        $this->assertEquals($cookies, $request->getCookieParams());
        $this->assertEquals($attributes, $request->getAttributes());
        $this->assertEquals($query, $request->getQueryParams());
        $this->assertEquals($body, $request->getBodyParams());
        $this->assertEquals($files, $request->getFileParams());
    }
}
