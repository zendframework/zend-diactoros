<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\SapiStreamEmitter;
use Zend\Diactoros\Server;

class ServerIntegrationTest extends TestCase
{
    public function testPassesBufferLevelToSapiStreamEmitter()
    {
        $currentObLevel = ob_get_level();
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $emitter = $this->prophesize(SapiStreamEmitter::class);
        $emitter
            ->emit(
                $response,
                $currentObLevel + 1
            )
            ->shouldBeCalled();

        $middleware = function ($req, $res) use ($request, $response) {
            TestCase::assertSame($request, $req);
            TestCase::assertSame($response, $res);
            return $res;
        };

        $server = new Server(
            $middleware,
            $request,
            $response
        );
        $server->setEmitter($emitter->reveal());
        $server->listen();

        ob_end_clean();
    }
}
