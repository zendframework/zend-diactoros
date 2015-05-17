<?php
namespace PhlyTest\Http\Response;

use Phly\Http\HeaderStack;  // test asset
use Phly\Http\Response;
use Phly\Http\Response\SapiEmitter;
use Phly\Http\SapiResponse; // test asset
use Phly\Http\Stream;
use PHPUnit_Framework_TestCase as TestCase;

class SapiEmitterTest extends TestCase
{
    public function setUp()
    {
        HeaderStack::reset();
        $this->emitter = new SapiEmitter();
    }

    public function tearDown()
    {
        HeaderStack::reset();
    }

    public function testEmitsResponseHeaders()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        ob_start();
        $this->emitter->emit($response);
        ob_end_clean();
        $this->assertContains('HTTP/1.1 200 OK', HeaderStack::stack());
        $this->assertContains('Content-Type: text/plain', HeaderStack::stack());
    }

    public function testEmitsMessageBody()
    {
        $response = (new Response())
            ->withStatus(200)
            ->withAddedHeader('Content-Type', 'text/plain');
        $response->getBody()->write('Content!');

        $this->expectOutputString('Content!');
        $this->emitter->emit($response);
    }
}
