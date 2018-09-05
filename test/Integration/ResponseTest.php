<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros\Integration;

use Http\Factory\Diactoros\RequestFactory;
use Http\Psr7Test\ResponseIntegrationTest;
use Zend\Diactoros\Response;

class ResponseTest extends ResponseIntegrationTest
{
    public static function setUpBeforeClass()
    {
        if (! class_exists(RequestFactory::class)) {
            self::markTestSkipped('You need to install http-interop/http-factory-diactoros to run integration tests');
        }
        parent::setUpBeforeClass();
    }

    public function createSubject()
    {
        return new Response();
    }
}
