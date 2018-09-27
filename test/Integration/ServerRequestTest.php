<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros\Integration;

use Http\Psr7Test\ServerRequestIntegrationTest;
use Zend\Diactoros\RequestFactory;
use Zend\Diactoros\ServerRequest;

class ServerRequestTest extends ServerRequestIntegrationTest
{
    public function createSubject()
    {
        return new ServerRequest($_SERVER);
    }
}
