<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\Stream;

use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function file_exists;
use function file_put_contents;
use function gmdate;
use function in_array;
use function preg_match;
use function sprintf;
use function strtotime;

use const CURLINFO_HTTP_CODE;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const LOCK_EX;

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
        $this->assertSame(200, $this->response->getStatusCode());
    }

    public function testStatusCodeMutatorReturnsCloneWithChanges()
    {
        $response = $this->response->withStatus(400);
        $this->assertNotSame($this->response, $response);
        $this->assertSame(400, $response->getStatusCode());
    }

    public function testReasonPhraseDefaultsToStandards()
    {
        $response = $this->response->withStatus(422);
        $this->assertSame('Unprocessable Entity', $response->getReasonPhrase());
    }

    private function fetchIanaStatusCodes() : DOMDocument
    {
        $updated = null;
        $ianaHttpStatusCodesFile = __DIR__ . '/TestAsset/.cache/http-status-codes.xml';
        $ianaHttpStatusCodes = null;
        if (file_exists($ianaHttpStatusCodesFile)) {
            $ianaHttpStatusCodes = new DOMDocument();
            $ianaHttpStatusCodes->load($ianaHttpStatusCodesFile);
            if (! $ianaHttpStatusCodes->relaxNGValidate(__DIR__ . '/TestAsset/http-status-codes.rng')) {
                $ianaHttpStatusCodes = null;
            }
        }
        if ($ianaHttpStatusCodes) {
            if (! getenv('ALWAYS_REFRESH_IANA_HTTP_STATUS_CODES')) {
                // use cached codes
                return $ianaHttpStatusCodes;
            }
            $xpath = new DOMXPath($ianaHttpStatusCodes);
            $xpath->registerNamespace('ns', 'http://www.iana.org/assignments');
            $updated = $xpath->query('//ns:updated')->item(0)->nodeValue;
            $updated = strtotime($updated);
        }

        $ch = curl_init('https://www.iana.org/assignments/http-status-codes/http-status-codes.xml');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP Curl');
        if ($updated) {
            $ifModifiedSince = sprintf(
                'If-Modified-Since: %s',
                gmdate('D, d M Y H:i:s \G\M\T', $updated)
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, [$ifModifiedSince]);
        }
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseCode === 304 && $ianaHttpStatusCodes) {
            // status codes did not change
            return $ianaHttpStatusCodes;
        }

        if ($responseCode === 200) {
            $downloadedIanaHttpStatusCodes = new DOMDocument();
            $downloadedIanaHttpStatusCodes->loadXML($response);
            if ($downloadedIanaHttpStatusCodes->relaxNGValidate(__DIR__ . '/TestAsset/http-status-codes.rng')) {
                file_put_contents($ianaHttpStatusCodesFile, $response, LOCK_EX);
                return $downloadedIanaHttpStatusCodes;
            }
        }
        if ($ianaHttpStatusCodes) {
            // return cached codes if available
            return $ianaHttpStatusCodes;
        }
        self::fail('Unable to retrieve IANA response status codes due to timeout or invalid XML');
    }

    public function ianaCodesReasonPhrasesProvider()
    {
        $ianaHttpStatusCodes = $this->fetchIanaStatusCodes();

        $ianaCodesReasonPhrases = [];

        $xpath = new DOMXPath($ianaHttpStatusCodes);
        $xpath->registerNamespace('ns', 'http://www.iana.org/assignments');

        $records = $xpath->query('//ns:record');

        foreach ($records as $record) {
            $value = $xpath->query('.//ns:value', $record)->item(0)->nodeValue;
            $description = $xpath->query('.//ns:description', $record)->item(0)->nodeValue;

            if (in_array($description, ['Unassigned', '(Unused)'])) {
                continue;
            }

            if (preg_match('/^([0-9]+)\s*\-\s*([0-9]+)$/', $value, $matches)) {
                for ($value = $matches[1]; $value <= $matches[2]; $value++) {
                    $ianaCodesReasonPhrases[] = [$value, $description];
                }
            } else {
                $ianaCodesReasonPhrases[] = [$value, $description];
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
        $this->assertSame($reasonPhrase, $response->getReasonPhrase());
    }

    public function testCanSetCustomReasonPhrase()
    {
        $response = $this->response->withStatus(422, 'Foo Bar!');
        $this->assertSame('Foo Bar!', $response->getReasonPhrase());
    }

    public function invalidReasonPhrases()
    {
        return [
            'true' => [ true ],
            'false' => [ false ],
            'array' => [ [ 200 ] ],
            'object' => [ (object) [ 'reasonPhrase' => 'Ok' ] ],
            'integer' => [99],
            'float' => [400.5],
            'null' => [null],
        ];
    }

    /**
     * @dataProvider invalidReasonPhrases
     */
    public function testWithStatusRaisesAnExceptionForNonStringReasonPhrases($invalidReasonPhrase)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->response->withStatus(422, $invalidReasonPhrase);
    }

    public function testConstructorRaisesExceptionForInvalidStream()
    {
        $this->expectException(InvalidArgumentException::class);

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
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame($headers, $response->getHeaders());
    }

    /**
     * @dataProvider validStatusCodes
     */
    public function testCreateWithValidStatusCodes($code)
    {
        $response = $this->response->withStatus($code);

        $result = $response->getStatusCode();

        $this->assertSame((int) $code, $result);
        $this->assertInternalType('int', $result);
    }

    public function validStatusCodes()
    {
        return [
            'minimum' => [100],
            'middle' => [300],
            'string-integer' => ['300'],
            'maximum' => [599],
        ];
    }

    /**
     * @dataProvider invalidStatusCodes
     */
    public function testCannotSetInvalidStatusCode($code)
    {
        $this->expectException(InvalidArgumentException::class);

        $this->response->withStatus($code);
    }

    public function invalidStatusCodes()
    {
        return [
            'true' => [ true ],
            'false' => [ false ],
            'array' => [ [ 200 ] ],
            'object' => [ (object) [ 'statusCode' => 200 ] ],
            'too-low' => [99],
            'float' => [400.5],
            'too-high' => [600],
            'null' => [null],
            'string' => ['foo'],
        ];
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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('stream');

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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($contains);

        new Response('php://memory', 200, $headers);
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
        $this->expectException(InvalidArgumentException::class);

        new Response('php://memory', 200, [$name => $value]);
    }
}
