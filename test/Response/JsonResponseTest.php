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
use Zend\Diactoros\Response\StringResponse;
use ZendTest\Diactoros\TestAsset\JsonSerializableObject;

class JsonResponseTest extends TestCase
{
    public function testJsonConstructor()
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

    public function testContentTypeCanBeOverwritten()
    {
        $data = [];
        $json = '[]';

        $response = new JsonResponse($data, 200, ['content-type' => 'foo/json']);
        $this->assertSame($json, (string) $response->getBody());
        $this->assertEquals('foo/json', $response->getHeaderLine('content-type'));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testJsonErrorHandling()
    {
        $recursiveObj = new \stdClass();
        $recursiveObj->recurs = $recursiveObj;

        new JsonResponse($recursiveObj);
    }

    /**
     * @expectedException Exception
     */
    public function testSetContentJsonSerializeError()
    {
        $serializable = new JsonSerializableObject();
        new JsonResponse($serializable);
    }
}
