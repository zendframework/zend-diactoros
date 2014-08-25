<?php
namespace PhlyTest\Http;

use Phly\Http\Output; // test asset
use Phly\Http\Server;
use PHPUnit_Framework_TestCase as TestCase;

require_once __DIR__ . '/TestAsset/Functions.php';

class ServerTest extends TestCase
{
    public function setUp()
    {
        Output::$headers = array();
        Output::$body    = null;

        $this->callback   = function ($req, $res, $done) { };
        $this->request    = $this->getMock('Psr\Http\Message\RequestInterface');
        $this->response   = $this->getMock('Phly\Http\ResponseInterface');
    }

    public function tearDown()
    {
        Output::$headers = array();
        Output::$body    = null;
    }

    public function testCreateServerFromRequestReturnsServerInstanceWithProvidedObjects()
    {
        $server = Server::createServerFromRequest(
            $this->callback,
            $this->request,
            $this->response
        );
        $this->assertInstanceOf('Phly\Http\Server', $server);
        $this->assertSame($this->callback, $server->callback);
        $this->assertSame($this->request, $server->request);
        $this->assertSame($this->response, $server->response);
    }

    public function testCreateServerFromRequestWillCreateResponseIfNotProvided()
    {
        $server = Server::createServerFromRequest(
            $this->callback,
            $this->request
        );
        $this->assertInstanceOf('Phly\Http\Server', $server);
        $this->assertSame($this->callback, $server->callback);
        $this->assertSame($this->request, $server->request);
        $this->assertInstanceOf('Phly\Http\Response', $server->response);
    }

    public function testCannotAccessArbitraryProperties()
    {
        $server = new Server(
            $this->callback,
            $this->request,
            $this->response
        );
        $prop = uniqid();
        $this->setExpectedException('OutOfBoundsException');
        $server->$prop;
    }

    public function testCreateServerWillCreateDefaultInstancesForRequestAndResponse()
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'HTTP_ACCEPT' => 'application/json',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'bar=baz',
        ];
        $server = Server::createServer($this->callback, $server);
        $this->assertInstanceOf('Phly\Http\Server', $server);
        $this->assertSame($this->callback, $server->callback);

        $this->assertInstanceOf('Phly\Http\Request', $server->request);
        $request = $server->request;
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/foo/bar', $request->getUrl()->path);
        $this->assertTrue($request->hasHeader('Accept'));

        $this->assertInstanceOf('Phly\Http\Response', $server->response);
    }

    public function testListenInvokesCallbackAndSendsResponse()
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'HTTP_ACCEPT' => 'application/json',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'bar=baz',
        ];

        $callback = function ($req, $res) {
            $res->addHeader('Content-Type', 'text/plain');
            $res->end('FOOBAR');
        };
        $server = Server::createServer($callback, $server);
        $server->listen();

        $this->assertContains('HTTP/1.1 200 OK', Output::$headers);
        $this->assertContains('Content-Type: text/plain', Output::$headers);
        $this->assertEquals('FOOBAR', Output::$body);
    }

    public function testListenEmitsStatusHeaderWithoutReasonPhraseIfNoReasonPhrase()
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'HTTP_ACCEPT' => 'application/json',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'bar=baz',
        ];

        $callback = function ($req, $res) {
            $res->setStatusCode(299);
            $res->addHeader('Content-Type', 'text/plain');
            $res->end('FOOBAR');
        };
        $server = Server::createServer($callback, $server);
        $server->listen();

        $this->assertContains('HTTP/1.1 299', Output::$headers);
        $this->assertContains('Content-Type: text/plain', Output::$headers);
        $this->assertEquals('FOOBAR', Output::$body);
    }
}
