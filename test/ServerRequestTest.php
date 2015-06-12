<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros;

use Maks3w\Psr7Assertions\PhpUnit\ServerRequestInterfaceTestsTrait;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\UploadedFile;
use Zend\Diactoros\Uri;

class ServerRequestTest extends TestCase
{
    use ServerRequestInterfaceTestsTrait;

    /** @var ServerRequest */
    protected $request;

    public function setUp()
    {
        $this->request = $this->createDefaultServerRequest();
    }

    protected function createDefaultServerRequest()
    {
        return new ServerRequest();
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

    public function testUploadedFilesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getUploadedFiles());
    }

    public function testParsedBodyIsEmptyByDefault()
    {
        $this->assertEmpty($this->request->getParsedBody());
    }

    public function testParsedBodyMutatorReturnsCloneWithChanges()
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->withParsedBody($value);
        $this->assertNotSame($this->request, $request);
        $this->assertEquals($value, $request->getParsedBody());
    }

    public function testAttributesAreEmptyByDefault()
    {
        $this->assertEmpty($this->request->getAttributes());
    }

    public function testSingleAttributesWhenEmptyByDefault()
    {
        $this->assertEmpty($this->request->getAttribute('does-not-exist'));
    }

    public function provideMethods()
    {
        return [
            'post' => ['POST', 'POST'],
            'get'  => ['GET', 'GET'],
            'null' => [null, 'GET'],
        ];
    }

    /**
     * @dataProvider provideMethods
     */
    public function testUsesProvidedConstructorArguments($parameterMethod, $methodReturned)
    {
        $server = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $server['server'] = true;

        $files = [
            'files' => new UploadedFile('php://temp', 0, 0),
        ];

        $uri = new Uri('http://example.com');
        $headers = [
            'host' => ['example.com'],
        ];

        $request = new ServerRequest(
            $server,
            $files,
            $uri,
            $parameterMethod,
            'php://memory',
            $headers
        );

        $this->assertEquals($server, $request->getServerParams());
        $this->assertEquals($files, $request->getUploadedFiles());

        $this->assertSame($uri, $request->getUri());
        $this->assertEquals($methodReturned, $request->getMethod());
        $this->assertEquals($headers, $request->getHeaders());

        $body = $request->getBody();
        $r = new ReflectionProperty($body, 'stream');
        $r->setAccessible(true);
        $stream = $r->getValue($body);
        $this->assertEquals('php://memory', $stream);
    }

    /**
     * @group 46
     */
    public function testCookieParamsAreAnEmptyArrayAtInitialization()
    {
        $request = new ServerRequest();
        $this->assertInternalType('array', $request->getCookieParams());
        $this->assertCount(0, $request->getCookieParams());
    }

    /**
     * @group 46
     */
    public function testQueryParamsAreAnEmptyArrayAtInitialization()
    {
        $request = new ServerRequest();
        $this->assertInternalType('array', $request->getQueryParams());
        $this->assertCount(0, $request->getQueryParams());
    }
}
