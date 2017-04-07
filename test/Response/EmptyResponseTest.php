<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros\Response;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\EmptyResponse;

class EmptyResponseTest extends TestCase
{
    public function testConstructor()
    {
        $response = new EmptyResponse(201);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testHeaderConstructor()
    {
        $response = EmptyResponse::withHeaders(['x-empty' => ['true']]);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals('true', $response->getHeaderLine('x-empty'));
    }
}
