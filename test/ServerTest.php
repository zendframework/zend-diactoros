<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\EmitterInterface;
use Zend\Diactoros\Server;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use ZendTest\Diactoros\TestAsset\HeaderStack;

use function array_pop;
use function uniqid;

class ServerTest extends TestCase
{
    /**
     * @var Callable
     */
    protected $callback;

    /**
     * @var ServerRequestInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var ResponseInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    public function setUp()
    {
        HeaderStack::reset();

        $this->callback   = function ($req, $res, $done) {
            //  Intentionally empty
        };
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
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

        $this->assertInstanceOf(Server::class, $server);
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

        $this->assertInstanceOf(Server::class, $server);
        $this->assertSame($this->callback, $server->callback);
        $this->assertSame($this->request, $server->request);
        $this->assertInstanceOf(Response::class, $server->response);
    }

    public function testCannotAccessArbitraryProperties()
    {
        $server = new Server(
            $this->callback,
            $this->request,
            $this->response
        );
        $prop = uniqid();

        $this->expectException(OutOfBoundsException::class);

        $server->$prop;
    }

    public function testEmitterSetter()
    {
        $server = new Server(
            $this->callback,
            $this->request,
            $this->response
        );
        $emmiter = $this->createMock(EmitterInterface::class);
        $emmiter->expects($this->once())->method('emit');

        $server->setEmitter($emmiter);

        $this->expectOutputString('');
        $server->listen();
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
        $server = Server::createServer($this->callback, $server, [], [], [], []);
        $this->assertInstanceOf(Server::class, $server);
        $this->assertSame($this->callback, $server->callback);

        $this->assertInstanceOf(ServerRequest::class, $server->request);
        $request = $server->request;
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/foo/bar', $request->getUri()->getPath());
        $this->assertTrue($request->hasHeader('Accept'));

        $this->assertInstanceOf(Response::class, $server->response);
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
            $res = $res->withAddedHeader('Content-Type', 'text/plain');
            $res->getBody()->write('FOOBAR');
            return $res;
        };
        $server = Server::createServer($callback, $server, [], [], [], []);

        $this->expectOutputString('FOOBAR');
        $server->listen();

        $this->assertTrue(HeaderStack::has('HTTP/1.1 200 OK'));
        $this->assertTrue(HeaderStack::has('Content-Type: text/plain'));
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

        $callback = function (ServerRequestInterface $req, ResponseInterface $res) {
            $res = $res->withStatus(299);
            $res = $res->withAddedHeader('Content-Type', 'text/plain');
            $res->getBody()->write('FOOBAR');
            return $res;
        };
        $server = Server::createServer($callback, $server, [], [], [], []);

        $this->expectOutputString('FOOBAR');
        $server->listen();

        $this->assertTrue(HeaderStack::has('HTTP/1.1 299'));
        $this->assertTrue(HeaderStack::has('Content-Type: text/plain'));
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

        $callback = function (ServerRequestInterface $req, ResponseInterface $res) {
            $res = $res->withAddedHeader('Content-Type', 'text/plain');
            $res->getBody()->write('100%');
            return $res;
        };
        $server = Server::createServer($callback, $server, [], [], [], []);

        $this->expectOutputString('100%');
        $server->listen();

        $this->assertTrue(HeaderStack::has('HTTP/1.1 200 OK'));
        $this->assertTrue(HeaderStack::has('Content-Type: text/plain'));
    }

    public function testEmitsHeadersWithMultipleValuesMultipleTimes()
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/foo/bar',
        ];

        $callback = function (ServerRequestInterface $req, ResponseInterface $res) {
            $res = $res->withAddedHeader('Content-Type', 'text/plain');
            $res = $res->withAddedHeader(
                'Set-Cookie',
                'foo=bar; expires=Wed, 1 Oct 2014 10:30; path=/foo; domain=example.com'
            );
            $res = $res->withAddedHeader(
                'Set-Cookie',
                'bar=baz; expires=Wed, 8 Oct 2014 10:30; path=/foo/bar; domain=example.com'
            );
            return $res;
        };
        $server = Server::createServer($callback, $server, [], [], [], []);

        $server->listen();

        $this->assertTrue(HeaderStack::has('HTTP/1.1 200 OK'));
        $this->assertTrue(HeaderStack::has('Content-Type: text/plain'));
        $this->assertTrue(
            HeaderStack::has('Set-Cookie: foo=bar; expires=Wed, 1 Oct 2014 10:30; path=/foo; domain=example.com')
        );
        $this->assertTrue(
            HeaderStack::has('Set-Cookie: bar=baz; expires=Wed, 8 Oct 2014 10:30; path=/foo/bar; domain=example.com')
        );

        return HeaderStack::stack();
    }

    /**
     * @group 5
     * @depends testEmitsHeadersWithMultipleValuesMultipleTimes
     */
    public function testHeaderOrderIsHonoredWhenEmitted($stack)
    {
        $header = array_pop($stack);
        $this->assertContains('Content-Type: text/plain', $header);

        $header = array_pop($stack);
        $this->assertContains(
            'Set-Cookie: bar=baz; expires=Wed, 8 Oct 2014 10:30; path=/foo/bar; domain=example.com',
            $header
        );

        $header = array_pop($stack);
        $this->assertContains(
            'Set-Cookie: foo=bar; expires=Wed, 1 Oct 2014 10:30; path=/foo; domain=example.com',
            $header
        );

        $header = array_pop($stack);
        $this->assertContains('HTTP/1.1 200 OK', $header);
    }

    public function testListenPassesCallableArgumentToCallback()
    {
        $phpunit  = $this;
        $invoked  = false;
        $request  = $this->request;
        $response = $this->response;

        $this->response
            ->expects($this->once())
            ->method('getHeaders')
            ->will($this->returnValue([]));

        $responseBody = new Stream('php://temp');
        $this->response
            ->expects($this->any())
            ->method('getBody')
            ->willReturn($responseBody);
        $this->response
            ->expects($this->any())
            ->method('withHeader')
            ->willReturnSelf();

        $final = function ($req, $res, $err = null) use ($phpunit, $request, $response, &$invoked) {
            $phpunit->assertSame($request, $req);
            $phpunit->assertSame($response, $res);
            $invoked = true;
        };

        $callback = function ($req, $res, callable $final = null) use ($phpunit) {
            if (! $final) {
                $phpunit->fail('No final callable passed!');
            }

            $final($req, $res);
        };

        $server = Server::createServerFromRequest(
            $callback,
            $this->request,
            $this->response
        );
        $server->listen($final);
        $this->assertTrue($invoked);
    }
}
