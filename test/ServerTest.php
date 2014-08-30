<?php
namespace PhlyTest\Http;

use Phly\Http\HeaderStack; // test asset
use Phly\Http\Server;
use PHPUnit_Framework_TestCase as TestCase;

require_once __DIR__ . '/TestAsset/Functions.php';

class ServerTest extends TestCase
{
    public function setUp()
    {
        HeaderStack::reset();

        $this->callback   = function ($req, $res, $done) { };
        $this->request    = $this->getMock('Psr\Http\Message\RequestInterface');
        $this->response   = $this->getMock('Psr\Http\Message\ResponseInterface');
    }

    public function tearDown()
    {
        HeaderStack::reset();
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
            $res->getBody()->write('FOOBAR');
        };
        $server = Server::createServer($callback, $server);

        $this->expectOutputString('FOOBAR');
        $server->listen();

        $this->assertContains('HTTP/1.1 200 OK', HeaderStack::stack());
        $this->assertContains('Content-Type: text/plain', HeaderStack::stack());
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
            $res->getBody()->write('FOOBAR');
        };
        $server = Server::createServer($callback, $server);

        $this->expectOutputString('FOOBAR');
        $server->listen();

        $this->assertContains('HTTP/1.1 299', HeaderStack::stack());
        $this->assertContains('Content-Type: text/plain', HeaderStack::stack());
    }

    public function testEnsurePercentCharactersDoNotResultInOutputError()
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
            $res->getBody()->write('100%');
        };
        $server = Server::createServer($callback, $server);

        $this->expectOutputString('100%');
        $server->listen();

        $this->assertContains('HTTP/1.1 200 OK', HeaderStack::stack());
        $this->assertContains('Content-Type: text/plain', HeaderStack::stack());
    }
}
