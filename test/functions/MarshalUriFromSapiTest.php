<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros\functions;

use PHPUnit\Framework\TestCase;
use function Zend\Diactoros\marshalUriFromSapi;

class MarshalUriFromSapiTest extends TestCase
{
    /**
     * @param string $httpsValue
     * @param string $expectedScheme
     * @dataProvider returnsUrlWithCorrectHttpSchemeFromArraysProvider
     */
    public function testReturnsUrlWithCorrectHttpSchemeFromArrays($httpsValue, $expectedScheme)
    {
        $server = [
            'HTTPS' => $httpsValue,
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => '80',
            'SERVER_ADDR' => '172.22.0.4',
            'REMOTE_PORT' => '36852',
            'REMOTE_ADDR' => '172.22.0.1',
            'SERVER_SOFTWARE' => 'nginx/1.11.8',
            'GATEWAY_INTERFACE' => 'CGI/1.1',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'DOCUMENT_ROOT' => '/var/www/public',
            'DOCUMENT_URI' => '/index.php',
            'REQUEST_URI' => '/api/messagebox-schema',
            'PATH_TRANSLATED' => '/var/www/public',
            'PATH_INFO' => '',
            'SCRIPT_NAME' => '/index.php',
            'CONTENT_LENGTH' => '',
            'CONTENT_TYPE' => '',
            'REQUEST_METHOD' => 'GET',
            'QUERY_STRING' => '',
            'SCRIPT_FILENAME' => '/var/www/public/index.php',
            'FCGI_ROLE' => 'RESPONDER',
            'PHP_SELF' => '/index.php',
        ];

        $headers = [
            'HTTP_COOKIE' => '',
            'HTTP_ACCEPT_LANGUAGE' => 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
            'HTTP_REFERER' => 'http://localhost:8080/index.html',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko)',
            'HTTP_ACCEPT' => 'application/json,*/*',
            'HTTP_CONNECTION' => 'keep-alive',
            'HTTP_HOST' => 'localhost:8080',
        ];

        $url = marshalUriFromSapi($server, $headers);

        self::assertSame($expectedScheme, $url->getScheme());
    }

    /**
     * @return array
     */
    public function returnsUrlWithCorrectHttpSchemeFromArraysProvider()
    {
        return [
            'on-lowercase' => ['on', 'https'],
            'on-uppercase' => ['ON', 'https'],
            'off-lowercase' => ['off', 'http'],
            'off-mixed-case' => ['oFf', 'http'],
            'neither-on-nor-off' => ['foo', 'http'],
            'empty' => ['', 'http'],
        ];
    }
}
