<?php
namespace PhlyTest\Http;

use Phly\Http\IncomingResponse as Response;
use Phly\Http\Stream;
use PHPUnit_Framework_TestCase as TestCase;

class IncomingResponseTest extends TestCase
{
    public function invalidStatusCodes()
    {
        return [
            'too-low' => [99],
            'too-high' => [600],
            'null' => [null],
            'bool' => [true],
            'string' => ['string'],
            'array' => [[200]],
            'object' => [(object) [200]],
        ];
    }

    /**
     * @dataProvider invalidStatusCodes
     */
    public function testCannotSetInvalidStatusCode($code)
    {
        $this->setExpectedException('InvalidArgumentException', 'status code');
        new Response($code, [], 'php://memory');
    }

    public function testReasonPhraseDefaultsToStandards()
    {
        $response = new Response(422, [], 'php://memory');
        $this->assertEquals('Unprocessable Entity', $response->getReasonPhrase());
    }

    public function testCanSetCustomReasonPhrase()
    {
        $response = new Response(422, [], 'php://memory', 'FOO BAR!');
        $this->assertEquals('FOO BAR!', $response->getReasonPhrase());
    }

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException', 'stream');
        new Response(200, [], [ 'TOTALLY INVALID' ]);
    }

    public function testCanCreateResponseWithAllMessageElements()
    {
        $stream = new Stream('php://memory');
        $stream->write('Foo bar!');

        $response = new Response(
            200,
            [
                'X-Foo' => 'Bar',
                'X-Bar' => ['Baz', 'Bat'],
            ],
            $stream,
            'so, yeah, okay'
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('so, yeah, okay', $response->getReasonPhrase());
        $this->assertEquals([
            'x-foo' => [ 'Bar' ],
            'x-bar' => ['Baz', 'Bat'],
        ], $response->getHeaders());
        $this->assertSame($stream, $response->getBody());
    }
}
