<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros\Response;

use PHPUnit\Framework\TestCase;
use swoole_http_response;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\SwooleEmitter;

class SwooleEmitterTest extends TestCase
{
    public function setUp()
    {
        if (! extension_loaded('swoole')) {
            $this->markTestSkipped('The Swoole extesion is not available');
        }
        $this->swooleResponse = $this->prophesize(swoole_http_response::class);
        $this->emitter = new SwooleEmitter($this->swooleResponse->reveal());
    }

    public function testEmit()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        $this->emitter->emit($response);

        $this->swooleResponse->status(200)->shouldHaveBeenCalled();
        $this->swooleResponse->header('Content-Type', 'text/plain')
                             ->shouldHaveBeenCalled();
        $this->swooleResponse->end('Content!')->shouldHaveBeenCalled();
    }

    public function testMultipleHeaders()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withHeader('Content-Type', 'text/plain')
            ->withHeader('Content-Length', '256');

        $this->emitter->emit($response);

        $this->swooleResponse->status(200)->shouldHaveBeenCalled();
        $this->swooleResponse->header('Content-Type', 'text/plain')
                             ->shouldHaveBeenCalled();
        $this->swooleResponse->header('Content-Length', '256')
                             ->shouldHaveBeenCalled();
    }

    public function testMultipleSetCookieHeaders()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Set-Cookie', 'foo=bar')
            ->withAddedHeader('Set-Cookie', 'bar=baz');

        $this->emitter->emit($response);

        $this->swooleResponse->status(200)->shouldHaveBeenCalled();
        $this->swooleResponse->header('Set-Cookie', 'foo=bar, bar=baz')
                             ->shouldHaveBeenCalled();
    }

    public function testEmitWithBigContentBody()
    {
        $content = base64_encode(random_bytes(SwooleEmitter::CHUNK_SIZE)); // CHUNK_SIZE * 1.33333
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write($content);

        $this->emitter->emit($response);

        $this->swooleResponse->status(200)->shouldHaveBeenCalled();
        $this->swooleResponse->header('Content-Type', 'text/plain')
                             ->shouldHaveBeenCalled();
        $this->swooleResponse->write(substr($content, 0, SwooleEmitter::CHUNK_SIZE))
                             ->shouldHaveBeenCalled();
        $this->swooleResponse->write(substr($content, SwooleEmitter::CHUNK_SIZE))
                             ->shouldHaveBeenCalled();
        $this->swooleResponse->end()->shouldHaveBeenCalled();
    }
}
