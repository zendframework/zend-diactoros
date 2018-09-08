<?php
/**
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros\Response;

use PHPUnit\Framework\TestCase;
use UnexpectedValueException;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\ArraySerializer;
use Zend\Diactoros\Stream;

class ArraySerializerTest extends TestCase
{
    public function testSerializeToArray()
    {
        $response = $this->createResponse();

        $message = ArraySerializer::toArray($response);

        $this->assertSame($this->createSerializedResponse(), $message);
    }

    public function testDeserializeFromArray()
    {
        $serializedResponse = $this->createSerializedResponse();

        $message = ArraySerializer::fromArray($serializedResponse);

        $response = $this->createResponse();

        $this->assertSame(Response\Serializer::toString($response), Response\Serializer::toString($message));
    }

    public function testMissingBodyParamInSerializedRequestThrowsException()
    {
        $serializedRequest = $this->createSerializedResponse();
        unset($serializedRequest['body']);

        $this->expectException(UnexpectedValueException::class);

        ArraySerializer::fromArray($serializedRequest);
    }

    private function createResponse()
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write('{"test":"value"}');

        return (new Response())
            ->withStatus(201, 'Custom')
            ->withProtocolVersion('1.1')
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat')
            ->withBody($stream);
    }

    private function createSerializedResponse()
    {
        return [
            'status_code' => 201,
            'reason_phrase' => 'Custom',
            'protocol_version' => '1.1',
            'headers' => [
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
    }
}
