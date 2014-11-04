<?php
namespace PhlyTest\Http;

use Phly\Http\OutgoingResponse as Response;
use PHPUnit_Framework_TestCase as TestCase;

class OutgoingResponseTest extends TestCase
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
        $this->response->setStatus(400);
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
        $this->response->setStatus($code);
    }

    public function testReasonPhraseDefaultsToStandards()
    {
        $this->response->setStatus(422);
        $this->assertEquals('Unprocessable Entity', $this->response->getReasonPhrase());
    }

    public function testCanSetCustomReasonPhrase()
    {
        $this->response->setStatus(422, 'Foo Bar!');
        $this->assertEquals('Foo Bar!', $this->response->getReasonPhrase());
    }

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Response([ 'TOTALLY INVALID' ]);
    }
}
