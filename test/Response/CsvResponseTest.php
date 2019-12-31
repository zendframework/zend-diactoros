<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros\Response;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\Response\CsvResponse;

/**
 * Class CsvResponseTest
 * @package ZendTest\Diactoros\Response
 * @coversDefaultClass \Zend\Diactoros\Response\CsvResponse
 */
class CsvResponseTest extends TestCase
{
    const VALID_CSV_BODY = <<<EOF
"first","last","email","dob",
"john","citizen","john.citizen@afakeemailaddress.com","01/01/1970",
EOF;

    public function testConstructorAcceptsBodyAsString()
    {
        $response = new CsvResponse(self::VALID_CSV_BODY);
        $this->assertSame(self::VALID_CSV_BODY, (string) $response->getBody());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConstructorAllowsPassingStatus()
    {
        $status = 404;

        $response = new CsvResponse(self::VALID_CSV_BODY, $status);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(self::VALID_CSV_BODY, (string) $response->getBody());
    }

    public function testConstructorAllowsPassingHeaders()
    {
        $status = 404;
        $headers = [
            'x-custom' => [ 'foo-bar' ],
        ];
        $filename = '';

        $response = new CsvResponse(self::VALID_CSV_BODY, $status, $filename, $headers);
        $this->assertSame(['foo-bar'], $response->getHeader('x-custom'));
        $this->assertSame('text/csv; charset=utf-8', $response->getHeaderLine('content-type'));
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(self::VALID_CSV_BODY, (string) $response->getBody());
    }

    public function testAllowsStreamsForResponseBody()
    {
        $stream = $this->prophesize(StreamInterface::class);
        $body   = $stream->reveal();
        $response = new CsvResponse($body);
        $this->assertSame($body, $response->getBody());
    }

    public function invalidContent()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['php://temp']],
            'object'     => [(object) ['php://temp']],
        ];
    }

    /**
     * @dataProvider invalidContent
     */
    public function testRaisesExceptionforNonStringNonStreamBodyContent($body)
    {
        $this->expectException(InvalidArgumentException::class);

        new CsvResponse($body);
    }

    /**
     * @group 115
     */
    public function testConstructorRewindsBodyStream()
    {
        $response = new CsvResponse(self::VALID_CSV_BODY);

        $actual = $response->getBody()->getContents();
        $this->assertSame(self::VALID_CSV_BODY, $actual);
    }
}
