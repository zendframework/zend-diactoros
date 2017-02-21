<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

class ResponseTest extends TestCase
{
    /**
     * @var Response
    */
    protected $response;

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

    protected function checkNewVersionIanaHttpStatusCodes(\DOMDocument $ianaHttpStatusCodes)
    {
        set_error_handler(function ($errno, $errstr) {
            throw new \ErrorException($errstr, 0, $errno);
        });

        $updateError = null;

        try {
            $xpath = new \DomXPath($ianaHttpStatusCodes);
            $xpath->registerNamespace('ns', 'http://www.iana.org/assignments');

            $updated = $xpath->query('//ns:updated')->item(0)->nodeValue;
            $lastModified  = new \DateTime($updated . ' +24 hour GMT');

            $options = [
              'http' => [
                  'method'  => 'GET',
                  'timeout' => 30,
                  'header' => [
                      'If-Modified-Since: ' . $lastModified->format("r") . "\r\n"
                  ]
              ],
            ];

            $contents = file_get_contents(
                'https://www.iana.org/assignments/http-status-codes/http-status-codes.xml',
                false,
                stream_context_create($options)
            );

            $http_status = 0;
            $remoteLastModified = $lastModified;

            if ($http_response_header) {
                for ($i = 0; $i < count($http_response_header); $i++) {
                    if (preg_match('/^HTTP\/[0-9\.]+\s*([0-9]+)\s*.+$/i', $http_response_header[$i], $matches) > 0) {
                        $http_status = $matches[1];
                    } elseif (preg_match('/^Last-Modified\s*:\s*(.+)$/i', $http_response_header[$i], $matches) > 0) {
                        $remoteLastModified = new \DateTime($matches[1]);
                    }
                }
            }

            if ($http_status == 200 && $remoteLastModified > $lastModified) {
                $ianaHttpStatusCodes->loadXml($contents);
                $validXml = $ianaHttpStatusCodes->relaxNGValidate(__DIR__ . '/TestAsset/http-status-codes.rng');

                if ($validXml) {
                    file_put_contents(__DIR__ . '/TestAsset/http-status-codes.xml', $contents);
                    print 'IANA "http-status-codes.xml" updated successful' . "\n";
                } else {
                    $ianaHttpStatusCodes->load(__DIR__ . '/TestAsset/http-status-codes.xml');
                }
            }
        } catch (\Exception $e) {
            $updateError = $e->getMessage();
        }

        restore_error_handler();

        if ($updateError) {
            print 'Error on IANA "http-status-codes.xml" update. Error: ' . $updateError . "\n";
        }

        return $ianaHttpStatusCodes;
    }

    public function ianaCodesReasonPhrasesProvider()
    {
        set_error_handler(function ($errno, $errstr) {
            throw new \ErrorException($errstr, 0, $errno);
        });

        try {
            $ianaHttpStatusCodes = new \DOMDocument();
            $ianaHttpStatusCodes->load(__DIR__ . '/TestAsset/http-status-codes.xml');
            $validXml = $ianaHttpStatusCodes->relaxNGValidate(__DIR__ . '/TestAsset/http-status-codes.rng');
        } catch (\Exception $e) {
            $xmlError = $e->getMessage();
            $validXml = false;
        }

        restore_error_handler();

        if (! $validXml) {
            $this->markTestIncomplete(
                'Invalid IANA "http-status-codes.xml". Error: ' . $xmlError
            );
            return null;
        }

        $ianaHttpStatusCodes = $this->checkNewVersionIanaHttpStatusCodes($ianaHttpStatusCodes);

        $ianaCodesReasonPhrases = [];

        $xpath = new \DomXPath($ianaHttpStatusCodes);
        $xpath->registerNamespace('ns', 'http://www.iana.org/assignments');

        $records = $xpath->query('//ns:record');

        foreach ($records as $record) {
            $value = $xpath->query('.//ns:value', $record)->item(0)->nodeValue;
            $description = $xpath->query('.//ns:description', $record)->item(0)->nodeValue;

            if ($description === 'Unassigned' || $description === '(Unused)') {
                continue;
            }

            $range = preg_match('/^([0-9]+)\s*\-\s*([0-9]+)$/', $value, $matches);

            if (! $range) {
                $ianaCodesReasonPhrases[] = [$value, $description];
            } else {
                for ($value = $matches[1]; $value <= $matches[2]; $value++) {
                    $ianaCodesReasonPhrases[] = [$value, $description];
                }
            }
        }

        return $ianaCodesReasonPhrases;
    }

    /**
     * @dataProvider ianaCodesReasonPhrasesProvider
     */
    public function testReasonPhraseDefaultsAgainstIana($code, $reasonPhrase)
    {
        $response = $this->response->withStatus($code);
        $this->assertEquals($reasonPhrase, $response->getReasonPhrase());
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


    public function invalidHeaderTypes()
    {
        return [
            'indexed-array' => [[['INVALID']], 'header name'],
            'null' => [['x-invalid-null' => null]],
            'true' => [['x-invalid-true' => true]],
            'false' => [['x-invalid-false' => false]],
            'object' => [['x-invalid-object' => (object) ['INVALID']]],
        ];
    }

    /**
     * @dataProvider invalidHeaderTypes
     * @group 99
     */
    public function testConstructorRaisesExceptionForInvalidHeaders($headers, $contains = 'header value type')
    {
        $this->setExpectedException('InvalidArgumentException', $contains);
        new Response('php://memory', 200, $headers);
    }

    public function testInvalidStatusCodeInConstructor()
    {
        $this->setExpectedException('InvalidArgumentException');

        new Response('php://memory', null);
    }

    public function testReasonPhraseCanBeEmpty()
    {
        $response = $this->response->withStatus(555);
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
        $request = new Response('php://memory', 200, [$name => $value]);
    }
}
