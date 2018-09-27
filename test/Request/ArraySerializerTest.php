<?php
/**
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros\Request;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Zend\Diactoros\Request;
use Zend\Diactoros\Request\ArraySerializer;
use Zend\Diactoros\Stream;
use Zend\Diactoros\Uri;

class ArraySerializerTest extends TestCase
{
    public function testSerializeToArray()
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write('{"test":"value"}');

        $request = (new Request())
            ->withMethod('POST')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat')
            ->withBody($stream);

        $message = ArraySerializer::toArray($request);

        $this->assertSame([
            'method' => 'POST',
            'request_target' => '/foo/bar?baz=bat',
            'uri' => 'http://example.com/foo/bar?baz=bat',
            'protocol_version' => '1.1',
            'headers' => [
                'Host' => [
                    'example.com',
                ],
                'Accept' => [
                    'application/json',
                ],
                'X-Foo-Bar' => [
                    'Baz',
                    'Bat'
                ],
            ],
            'body' => '{"test":"value"}',
        ], $message);
    }

    public function testDeserializeFromArray()
    {
        $serializedRequest = [
            'method' => 'POST',
            'request_target' => '/foo/bar?baz=bat',
            'uri' => 'http://example.com/foo/bar?baz=bat',
            'protocol_version' => '1.1',
            'headers' => [
                'Host' => [
                    'example.com',
                ],
                'Accept' => [
                    'application/json',
                ],
                'X-Foo-Bar' => [
                    'Baz',
                    'Bat'
                ],
            ],
            'body' => '{"test":"value"}',
        ];

        $message = ArraySerializer::fromArray($serializedRequest);

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('{"test":"value"}');

        $request = (new Request())
            ->withMethod('POST')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat')
            ->withBody($stream);

        $this->assertSame(Request\Serializer::toString($request), Request\Serializer::toString($message));
    }

    public function testMissingBodyParamInSerializedRequestThrowsException()
    {
        $serializedRequest = [
            'method' => 'POST',
            'request_target' => '/foo/bar?baz=bat',
            'uri' => 'http://example.com/foo/bar?baz=bat',
            'protocol_version' => '1.1',
            'headers' => [
                'Host' => [
                    'example.com',
                ],
                'Accept' => [
                    'application/json',
                ],
                'X-Foo-Bar' => [
                    'Baz',
                    'Bat'
                ],
            ],
        ];

        $this->expectException(UnexpectedValueException::class);

        ArraySerializer::fromArray($serializedRequest);
    }
}
