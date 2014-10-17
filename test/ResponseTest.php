<?php
namespace PhlyTest\Http;

use Phly\Http\Response;
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

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Response([ 'TOTALLY INVALID' ]);
    }
}
