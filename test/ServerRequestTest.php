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

    public function testQueryParamsMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->setQueryParams($value);
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($value, $request->getQueryParams());
    }

    public function testCookiesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getCookieParams());
    }

    public function testCookiesMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->setCookieParams($value);
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($value, $request->getCookieParams());
    }

    public function testFileParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getFileParams());
    }

    public function testBodyParamsAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getBodyParams());
    }

    public function testBodyParamsMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->setBodyParams($value);
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($value, $request->getBodyParams());
    }

    public function testAttributesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getAttributes());
    }

    /**
     * @depends testAttributesAreEmptyByDefault
     */
    public function testAttributesMutatorReturnsCloneWithChanges()
    {
        $params = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];
        $request = $this->request->setAttributes($params);
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($params, $request->getAttributes());
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
