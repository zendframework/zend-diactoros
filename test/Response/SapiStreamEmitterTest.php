<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros\Response;

use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\CallbackStream;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\SapiStreamEmitter;
use ZendTest\Diactoros\TestAsset\HeaderStack;

class SapiStreamEmitterTest extends SapiEmitterTest
{
    public function setUp()
    {
        HeaderStack::reset();
        $this->emitter = new SapiStreamEmitter();
    }

    public function testEmitCallbackStreamResponse()
    {
        $stream = new CallbackStream(function () {
            return 'it works';
        });
        $response = (new Response())
            ->withStatus(200)
            ->withBody($stream);
        ob_start();
        $this->emitter->emit($response);
        $this->assertEquals('it works', ob_get_clean());
    }

    public function testDoesNotInjectContentLengthHeaderIfStreamSizeIsUnknown()
    {
        $stream = $this->prophesize('Psr\Http\Message\StreamInterface');
        $stream->__toString()->willReturn('Content!');
        $stream->isSeekable()->willReturn(false);
        $stream->isReadable()->willReturn(false);
        $stream->eof()->willReturn(true);
        $stream->rewind()->willReturn(true);
        $stream->getSize()->willReturn(null);
        $response = (new Response())
            ->withStatus(200)
            ->withBody($stream->reveal());

        ob_start();
        $this->emitter->emit($response);
        ob_end_clean();
        foreach (HeaderStack::stack() as $header) {
            $this->assertNotContains('Content-Length:', $header);
        }
    }

    public function emitBodyProvider()
    {
        return [
            [true,   '01234567890123456789'    , 10, 2],
            [true,   '012345678901234567890123', 10, 3],
            [false,  '01234567890123456789'    , 10, 2],
            [false,  '012345678901234567890123', 10, 3],
        ];
    }

    /**
     * @dataProvider emitBodyProvider
    */
    public function testEmitBody($seekable, $contents, $maxBufferLength, $expectedReads = 0)
    {
        $position = 0;

        $stream = $this->prophesize('Psr\Http\Message\StreamInterface');
        $stream->getSize()->willReturn(strlen($contents));
        $stream->isSeekable()->willReturn($seekable);
        $stream->isReadable()->willReturn(true);
        $stream->rewind()->willReturn(true);

        $stream->eof()->will(function () use (&$contents, &$position){
            return !isset($contents[$position]);
        });

        $stream->read(Argument::type('integer'))->will(function ($args) use (&$contents, &$position){
                $data = substr($contents, $position, $args[0]);
                $position += strlen($data);
                return $data;
            });

        $response = (new Response())
            ->withStatus(200)
            ->withBody($stream->reveal());

        ob_start();
        $this->emitter->emit($response, $maxBufferLength);
        $stream->rewind()->shouldBeCalledTimes($seekable ? 1 : 0);
        $stream->read(Argument::type('integer'))->shouldBeCalledTimes($expectedReads);
        $this->assertEquals($contents, ob_get_clean());

    }

    public function emitBodyRangeProvider()
    {
        return [
            [true,  '01234567890123456789'    , ['bytes', 10, 20, '*'], 10, 1],
            [true,  '012345678901234567890123', ['bytes', 10, 40, '*'], 10, 2],
            [false, '01234567890123456789'    , ['bytes', 11, 20, '*'], 10, 1],
            [false, '012345678901234567890123', ['bytes', 11, 40, '*'], 10, 2],
        ];
    }

    /**
     * @dataProvider emitBodyRangeProvider
     */
    public function testEmitBodyRange($seekable, $contents, $range, $maxBufferLength, $expectedReads = 0)
    {
        list($unit, $first, $last, $length) = $range;

        $position = $first;

        $stream = $this->prophesize('Psr\Http\Message\StreamInterface');
        $stream->getSize()->willReturn(strlen($contents));
        $stream->isSeekable()->willReturn($seekable);
        $stream->isReadable()->willReturn(true);
        $stream->seek(Argument::type('integer'))->will(function ($args) use (&$position){
            $position = $args[0];
            return true;
        });

        $stream->eof()->will(function () use (&$contents, &$position){
            return !isset($contents[$position]);
        });

        $stream->read(Argument::type('integer'))->will(function ($args) use (&$contents, &$position){
            $data = substr($contents, $position, $args[0]);
            $position += strlen($data);
            return $data;
        });

        $response = (new Response())
            ->withStatus(200)
            ->withHeader('Content-Range', "$unit $first-$last/$length")
            ->withBody($stream->reveal());

        ob_start();
        $this->emitter->emit($response, $maxBufferLength);
        $stream->seek(Argument::type('integer'))->shouldBeCalledTimes($seekable ? 1 : 0);
        $stream->read(Argument::type('integer'))->shouldBeCalledTimes($expectedReads);
        $this->assertEquals(substr($contents, $first, $last - $first + 1), ob_get_clean());
    }

    public function contentRangeProvider()
    {
        return [
            ['bytes 0-2/*', 'Hello world', 'Hel'],
            ['bytes 3-6/*', 'Hello world', 'lo w'],
            ['items 0-0/1', 'Hello world', 'Hello world'],
        ];
    }

    /**
     * @dataProvider contentRangeProvider
     */
    public function testContentRange($header, $body, $expected)
    {
        $response = (new Response())
            ->withHeader('Content-Range', $header);

        $response->getBody()->write($body);

        ob_start();
        $this->emitter->emit($response);
        $this->assertEquals($expected, ob_get_clean());
    }

    public function testContentRangeUnseekableBody()
    {
        $body = new CallbackStream(function () {
            return 'Hello world';
        });
        $response = (new Response())
            ->withBody($body)
            ->withHeader('Content-Range', 'bytes 3-6/*');

        ob_start();
        $this->emitter->emit($response);
        $this->assertEquals('lo w', ob_get_clean());
    }
}
