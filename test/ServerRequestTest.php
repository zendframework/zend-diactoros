<?php
namespace PhlyTest\Http;

use Phly\Http\ServerRequest;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;

class ServerRequestTest extends TestCase
{
    public function setUp()
    {
        $this->request = new ServerRequest();
    }

    public function testServerParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getServerParams());
    }

    public function testQueryParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getQueryParams());
    }

    public function testQueryParamsAreMutable()
    {
        $value = ['foo' => 'bar'];
        $this->request->setQueryParams($value);
        $this->assertEquals($value, $this->request->getQueryParams());
    }

    public function testCookiesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getCookieParams());
    }

    public function testCookiesAreMutable()
    {
        $value = ['foo' => 'bar'];
        $this->request->setCookieParams($value);
        $this->assertEquals($value, $this->request->getCookieParams());
    }

    public function testFileParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getFileParams());
    }

    public function testBodyParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getBodyParams());
    }

    public function testBodyParamsAreMutable()
    {
        $value = ['foo' => 'bar'];
        $this->request->setBodyParams($value);
        $this->assertEquals($value, $this->request->getBodyParams());
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

    public function testStreamAndServerAndFilesMayBeSetAsDiscreteConstructorArguments()
    {
        $server = $files = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $server['server'] = true;
        $files['files']   = true;

        $request = new ServerRequest(
            'php://memory',
            $server,
            $files
        );

        $body = $request->getBody();
        $r = new ReflectionProperty($body, 'stream');
        $r->setAccessible(true);
        $stream = $r->getValue($body);

        $this->assertEquals('php://memory', $stream);
        $this->assertEquals($server, $request->getServerParams());
        $this->assertEquals($files, $request->getFileParams());
    }
}
