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

    public function testStatusCodeMutatorReturnsCloneWithChanges()
    {
        $response = $this->response->withStatus(400);
        $this->assertNotSame($this->response, $response);
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function invalidStatusCodes()
    {
        return [
            'too-low' => [99],
            'too-high' => [600],
            'null' => [null],
            'bool' => [true],
            'string' => ['foo'],
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
        $response = $this->response->withStatus($code);
    }

    public function testReasonPhraseDefaultsToStandards()
    {
        $response = $this->response->withStatus(422);
        $this->assertEquals('Unprocessable Entity', $response->getReasonPhrase());
    }

    public function testCanSetCustomReasonPhrase()
    {
        $response = $this->response->withStatus(422, 'Foo Bar!');
        $this->assertEquals('Foo Bar!', $response->getReasonPhrase());
    }

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Response([ 'TOTALLY INVALID' ]);
    }

    public function testConstructorCanAcceptAllMessageParts()
    {
        $body = new Stream('php://memory');
        $status = 302;
        $headers = [
            'location' => [ 'http://example.com/' ],
        ];

        $response = new Response($body, $status, $headers);
        $this->assertSame($body, $response->getBody());
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($headers, $response->getHeaders());
    }

    public function invalidStatus()
    {
        return [
            'true' => [ true ],
            'false' => [ false ],
            'float' => [ 100.1 ],
            'bad-string' => [ 'Two hundred' ],
            'array' => [ [ 200 ] ],
            'object' => [ (object) [ 'statusCode' => 200 ] ],
            'too-small' => [ 1 ],
            'too-big' => [ 600 ],
        ];
    }

    /**
     * @dataProvider invalidStatus
     */
    public function testConstructorRaisesExceptionForInvalidStatus($code)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid status code');
        new Response('php://memory', $code);
    }

    public function invalidResponseBody()
    {
        return [
            'true'       => [ true ],
            'false'      => [ false ],
            'int'        => [ 1 ],
            'float'      => [ 1.1 ],
            'array'      => [ ['BODY'] ],
            'stdClass'   => [ (object) [ 'body' => 'BODY'] ],
        ];
    }

    /**
     * @dataProvider invalidResponseBody
     */
    public function testConstructorRaisesExceptionForInvalidBody($body)
    {
        $this->setExpectedException('InvalidArgumentException', 'stream');
        new Response($body);
    }

    public function testConstructorIgonoresInvalidHeaders()
    {
        $headers = [
            [ 'INVALID' ],
            'x-invalid-null' => null,
            'x-invalid-true' => true,
            'x-invalid-false' => false,
            'x-invalid-int' => 1,
            'x-invalid-object' => (object) ['INVALID'],
            'x-valid-string' => 'VALID',
            'x-valid-array' => [ 'VALID' ],
        ];
        $expected = [
            'x-valid-string' => [ 'VALID' ],
            'x-valid-array' => [ 'VALID' ],
        ];
        $response = new Response('php://memory', null, $headers);
        $this->assertEquals($expected, $response->getHeaders());
    }

    public function testReasonPhraseCanBeEmpty()
    {
        $response = $this->response->withStatus(599);
        $this->assertInternalType('string', $response->getReasonPhrase());
        $this->assertEmpty($response->getReasonPhrase());
    }

    public function headersWithInjectionVectors()
    {
        return [
            'name-with-cr'           => ["X-Foo\r-Bar", 'value'],
            'name-with-lf'           => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf'         => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf'        => ["X-Foo\r\n\r\n-Bar", 'value'],
            'value-with-cr'          => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf'          => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf'        => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf'       => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr'    => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf'    => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf'  => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf' => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
        ];
    }

    /**
     * @group ZF2015-04
     * @dataProvider headersWithInjectionVectors
     */
    public function testConstructorRaisesExceptionForHeadersWithCRLFVectors($name, $value)
    {
        $this->setExpectedException('InvalidArgumentException');
        $request = new Response('php://memory', 200, [$name =>  $value]);
    }
}
