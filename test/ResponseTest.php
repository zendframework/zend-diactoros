<?php
namespace PhlyTest\Http;

use Phly\Http\Response;
use Phly\Http\Stream;
use PHPUnit_Framework_TestCase as TestCase;

class ResponseTest extends TestCase
{
    public function setUp()
    {
        $this->response = new Response();
    }

    public function testStatusCodeIs200ByDefault()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testStatusCodeIsMutable()
    {
        $this->response->setStatusCode(400);
        $this->assertEquals(400, $this->response->getStatusCode());
    }

    public function invalidStatusCodes()
    {
        return [
            'too-low' => [99],
            'too-high' => [600],
            'null' => [null],
            'bool' => [true],
            'string' => ['100'],
            'array' => [[200]],
            'object' => [(object) [200]],
        ];
    }

    /**
     * @dataProvider invalidStatusCodes
     */
    public function testCannotSetInvalidStatusCode($code)
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->response->setStatusCode($code);
    }

    public function testReasonPhraseDefaultsToStandards()
    {
        $this->response->setStatusCode(422);
        $this->assertEquals('Unprocessable Entity', $this->response->getReasonPhrase());
    }

    public function testCanSetCustomReasonPhrase()
    {
        $this->response->setStatusCode(422);
        $this->response->setReasonPhrase('FOO BAR!');
        $this->assertEquals('FOO BAR!', $this->response->getReasonPhrase());
    }

    public function testIsNotCompleteByDefault()
    {
        $this->assertFalse($this->response->isComplete());
    }

    public function testCallingEndMarksAsComplete()
    {
        $this->response->end();
        $this->assertTrue($this->response->isComplete());
    }

    public function testWriteAppendsBody()
    {
        $this->response->write("First\n");
        $this->assertContains('First', (string) $this->response->getBody());
        $this->response->write("Second\n");
        $this->assertContains('First', (string) $this->response->getBody());
        $this->assertContains('Second', (string) $this->response->getBody());
    }

    public function testCannotMutateResponseAfterCallingEnd()
    {
        $this->response->setStatusCode(201);
        $this->response->write("First\n");
        $this->response->end('DONE');

        $this->response->setStatusCode(200);
        $this->response->setHeader('X-Foo', 'Foo');
        $this->response->write('MOAR!');

        $this->assertEquals(201, $this->response->getStatusCode());
        $this->assertFalse($this->response->hasHeader('X-Foo'));
        $this->assertNotContains('MOAR!', (string) $this->response->getBody());
        $this->assertContains('First', (string) $this->response->getBody());
        $this->assertContains('DONE', (string) $this->response->getBody());
    }

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Response([ 'TOTALLY INVALID' ]);
    }

    public function testSetBodyReturnsEarlyIfComplete()
    {
        $this->response->end('foo');

        $body = new Stream('php://memory', 'r+');
        $this->response->setBody($body);

        $this->assertEquals('foo', (string) $this->response->getBody());
    }

    public function testSetHeadersDelegatesToParent()
    {
        $this->response->setHeaders([
            'Content-Type' => 'application/json',
        ]);
        $this->assertTrue($this->response->hasHeader('Content-Type'));
    }

    public function testSetHeadersDoesNothingIfComplete()
    {
        $this->response->end('foo');
        $this->response->setHeaders([
            'Content-Type' => 'application/json',
        ]);
        $this->assertFalse($this->response->hasHeader('Content-Type'));
    }

    public function testAddHeaderDoesNothingIfComplete()
    {
        $this->response->end('foo');
        $this->response->addHeader('Content-Type', 'application/json');
        $this->assertFalse($this->response->hasHeader('Content-Type'));
    }

    public function testAddHeadersDelegatesToParent()
    {
        $this->response->addHeaders([
            'Content-Type' => 'application/json',
        ]);
        $this->assertTrue($this->response->hasHeader('Content-Type'));
    }

    public function testAddHeadersDoesNothingIfComplete()
    {
        $this->response->end('foo');
        $this->response->addHeaders([
            'Content-Type' => 'application/json',
        ]);
        $this->assertFalse($this->response->hasHeader('Content-Type'));
    }

    public function testCallingEndMultipleTimesDoesNothingAfterFirstCall()
    {
        $this->response->end('foo');
        $this->response->end('bar');
        $this->assertEquals('foo', (string) $this->response->getBody());
    }
}
