<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros\Response;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Diactoros\Response\JsonResponse;

class JsonResponseTest extends TestCase
{
    public function testConstructorAcceptsDataAndCreatesJsonEncodedMessageBody()
    {
        $data = [
            'nested' => [
                'json' => [
                    'tree',
                ],
            ],
        ];
        $json = '{"nested":{"json":["tree"]}}';

        $response = new JsonResponse($data);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame($json, (string) $response->getBody());
    }

    public function testNullValuePassedToConstructorRendersEmptyJsonObjectInBody()
    {
        $response = new JsonResponse(null);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame('{}', (string) $response->getBody());
    }

    public function scalarValuesForJSON()
    {
        return [
            'false'        => [false],
            'true'         => [true],
            'zero'         => [0],
            'int'          => [1],
            'zero-float'   => [0.0],
            'float'        => [1.1],
            'empty-string' => [''],
            'string'       => ['string'],
        ];
    }

    /**
     * @dataProvider scalarValuesForJSON
     */
    public function testScalarValuePassedToConstructorRendersValueWithinJSONArray($value)
    {
        $response = new JsonResponse($value);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
        $this->assertSame(json_encode([$value], JSON_UNESCAPED_SLASHES), (string) $response->getBody());
    }

    public function testCanProvideStatusCodeToConstructor()
    {
        $response = new JsonResponse(null, 404);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testCanProvideAlternateContentTypeViaHeadersPassedToConstructor()
    {
        $response = new JsonResponse(null, 200, ['content-type' => 'foo/json']);
        $this->assertEquals('foo/json', $response->getHeaderLine('content-type'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testJsonErrorHandling()
    {
        // Serializing something that is not serializable.
        $resource = fopen('php://memory', 'r');
        new JsonResponse($resource);
    }
}
