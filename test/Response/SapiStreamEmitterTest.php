<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros\Response;

use Prophecy\Argument;
use Zend\Diactoros\CallbackStream;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\SapiStreamEmitter;
use ZendTest\Diactoros\TestAsset\HeaderStack;
use Zend\Diactoros\Response\JsonResponse;

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

    private function expandTestData()
    {
        $initialData = func_get_args();

        $initialData = array_filter($initialData, 'is_array');

        $resultData = [];

        $closureExpand = function ($item, $key, $parameters) use (& $closureExpand) {
            if (! is_array($item)) {
                $item = [$item];
            }

            $parameters['items'] = array_merge($parameters['items'], $item);

            if (count($parameters['initialData']) > 0) {
                array_walk(array_shift($parameters['initialData']), $closureExpand, $parameters);
            } else {
                $parameters['resultData'][] = $parameters['items'];
            }
        };

        array_walk(array_shift($initialData), $closureExpand, [
            'initialData' => $initialData,
            'items' => [],
            'resultData' => & $resultData]);

        return $resultData;
    }

    private function getStreamProphecy(& $contents, & $size, & $position, & $peakBufferLength)
    {
        $stream = $this->prophesize('Psr\Http\Message\StreamInterface');

        $stream->__toString()->will(function () use (& $contents, & $position, $size) {
            if (is_callable($contents)) {
                $data = $contents(0);
            } else {
                $data = $contents;
            }

            $position = $size;

            return $data;
        });

        $stream->getSize()->willReturn($size);

        $stream->tell()->will(function () use (& $position) {
            return $position;
        });

        $stream->eof()->will(function () use ($size, & $position) {
            return ($position >= $size);
        });

        $stream->seek(Argument::type('integer'), Argument::any())->will(function ($args) use ($size, & $position) {
            if ($args[0] < $size) {
                $position = $args[0];
                return true;
            }

            return false;
        });

        $stream->rewind()->will(function () use (& $position) {
            $position = 0;
            return true;
        });

        $stream->read(Argument::type('integer'))
            ->will(function ($args) use (& $contents, & $position, & $peakBufferLength) {
                if (is_callable($contents)) {
                    $data = $contents($position, $args[0]);
                } else {
                    $data = substr($contents, $position, $args[0]);
                }

                if ($args[0] > $peakBufferLength) {
                    $peakBufferLength = $args[0];
                }

                $position += strlen($data);

                return $data;
            });

        $stream->getContents()->will(function () use (& $contents, & $position) {
            if (is_callable($contents)) {
                $remainingContents = $contents($position);
            } else {
                $remainingContents = substr($contents, $position);
            }

            $position += strlen($remainingContents);

            return $remainingContents;
        });

        return $stream;
    }

    public function emitStreamResponseProvider()
    {
        $capabilities = [
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];


        $contents = [
            ['01234567890987654321'],
            ['01234567890987654321012'],
        ];

        $bufferLengths = [
          [10],
          [20],
          [100],
        ];

        return $this->expandTestData($capabilities, $contents, $bufferLengths);
    }

    /**
     * @dataProvider emitStreamResponseProvider
     */
    public function testEmitStreamResponse($seekable, $readable, $contents, $maxBufferLength)
    {
        $size = strlen($contents);
        $position = 0;
        $peakBufferLength = 0;
        $rewindCalled = false;
        $fullContentsCalled = false;

        $stream = $this->getStreamProphecy($contents, $size, $position, $peakBufferLength);
        $stream->isSeekable()->willReturn($seekable);
        $stream->isReadable()->willReturn($readable);

        $response = (new Response())
            ->withStatus(200)
            ->withBody($stream->reveal());

        ob_start();
        $this->emitter->emit($response, $maxBufferLength);
        $emittedContents = ob_get_clean();

        if ($seekable) {
            $rewindPredictionClosure = function () use (& $rewindCalled) {
                $rewindCalled = true;
            };

            $stream->rewind()->should($rewindPredictionClosure);
            $stream->seek(0)->should($rewindPredictionClosure);
            $stream->seek(0, SEEK_SET)->should($rewindPredictionClosure);
        } else {
            $stream->rewind()->shouldNotBeCalled();
            $stream->seek(Argument::type('integer'), Argument::any())->shouldNotBeCalled();
        }

        if ($readable) {
            $stream->__toString()->shouldNotBeCalled();
            $stream->read(Argument::type('integer'))->shouldBeCalled();
            $stream->eof()->shouldBeCalled();
            $stream->getContents()->shouldNotBeCalled();
        } else {
            $fullContentsPredictionClosure = function () use (& $fullContentsCalled) {
                $fullContentsCalled = true;
            };

            $stream->__toString()->should($fullContentsPredictionClosure);
            $stream->read(Argument::type('integer'))->shouldNotBeCalled();
            $stream->eof()->shouldNotBeCalled();

            if ($seekable) {
                $stream->getContents()->should($fullContentsPredictionClosure);
            } else {
                $stream->getContents()->shouldNotBeCalled();
            }
        }

        $stream->checkProphecyMethodsPredictions();

        $this->assertEquals($seekable, $rewindCalled);
        $this->assertEquals(! $readable, $fullContentsCalled);
        $this->assertEquals($contents, $emittedContents);
        $this->assertLessThanOrEqual($maxBufferLength, $peakBufferLength);
    }

    public function emitRangeStreamResponseProvider()
    {
        $capabilities = [
                [true,   true],
                [true,  false],
                [false,  true],
                [false, false],
        ];

        $ranges = [
                [['bytes', 10, 20, '*']],
                [['bytes', 10, 100, '*']],
        ];

        $contents = [
                ['01234567890987654321'],
                ['01234567890987654321012'],
        ];

        $bufferLengths = [
                [10],
                [20],
                [100],
        ];

        return $this->expandTestData($capabilities, $ranges, $contents, $bufferLengths);
    }


    /**
     * @dataProvider emitRangeStreamResponseProvider
     */
    public function testEmitRangeStreamResponse($seekable, $readable, array $range, $contents, $maxBufferLength)
    {
        list($unit, $first, $last, $length) = $range;
        $size = strlen($contents);

        if ($readable && ! $seekable) {
            $position = $first;
        } else {
            $position = 0;
        }

        $peakBufferLength = 0;
        $seekCalled = false;

        $stream = $this->getStreamProphecy($contents, $size, $position, $peakBufferLength);
        $stream->isSeekable()->willReturn($seekable);
        $stream->isReadable()->willReturn($readable);

        $response = (new Response())
            ->withStatus(200)
            ->withHeader('Content-Range', 'bytes ' . $first . '-' . $last . '/*')
            ->withBody($stream->reveal());

        ob_start();
        $this->emitter->emit($response, $maxBufferLength);
        $emittedContents = ob_get_clean();

        $stream->rewind()->shouldNotBeCalled();

        if ($seekable) {
            $seekPredictionClosure = function () use (& $seekCalled) {
                $seekCalled = true;
            };

            $stream->seek($first)->should($seekPredictionClosure);
            $stream->seek($first, SEEK_SET)->should($seekPredictionClosure);
        } else {
            $stream->seek(Argument::type('integer'), Argument::any())->shouldNotBeCalled();
        }

        $stream->__toString()->shouldNotBeCalled();

        if ($readable) {
            $stream->read(Argument::type('integer'))->shouldBeCalled();
            $stream->eof()->shouldBeCalled();
            $stream->getContents()->shouldNotBeCalled();
        } else {
            $stream->read(Argument::type('integer'))->shouldNotBeCalled();
            $stream->eof()->shouldNotBeCalled();
            $stream->getContents()->shouldBeCalled();
        }

        $stream->checkProphecyMethodsPredictions();

        $this->assertEquals($seekable, $seekCalled);
        $this->assertEquals(substr($contents, $first, $last - $first + 1), $emittedContents);
        $this->assertLessThanOrEqual($maxBufferLength, $peakBufferLength);
    }

    public function emitMemoryUsageProvider()
    {
        $capabilitiesLimitsRanges = [
            [true,   true,  1000,   20,       null],
            [true,  false,   100,  320,       null],
            [false,  true,  1000,   20,       null],
            [false, false,   100,  320,       null],
            [true,   true,  1000,   20,   [25, 75]],
            [false,  true,  1000,   20,   [25, 75]],
            [true,   true,  1000,   20, [250, 750]],
            [false,  true,  1000,   20, [250, 750]],
        ];

        $bufferLengths = [
                [512],
                [4096],
                [8192],
        ];

        return $this->expandTestData($capabilitiesLimitsRanges, $bufferLengths);
    }

    /**
     * @dataProvider emitMemoryUsageProvider
     */
    public function testEmitMemoryUsage(
        $seekable,
        $readable,
        $sizeBlocks,
        $maxAllowedBlocks,
        $rangeBlocks,
        $maxBufferLength
    ) {

        $sizeBytes = $maxBufferLength * $sizeBlocks;
        $maxAllowedMemoryUsage = $maxBufferLength * $maxAllowedBlocks;
        $peakBufferLength = 0;
        $peakMemoryUsage = 0;

        $position = 0;

        if ($rangeBlocks) {
            $first    = $maxBufferLength * $rangeBlocks[0];
            $last     = $maxBufferLength * $rangeBlocks[1];

            if ($readable && ! $seekable) {
                $position = $first;
            }
        }

        $closureTrackMemoryUsage = function () use (& $peakMemoryUsage) {
            $peakMemoryUsage = max($peakMemoryUsage, memory_get_usage());
        };

        $closureContents = function ($position, $length = null) use (& $sizeBytes) {
            if (! $length) {
                $length = $sizeBytes - $position;
            }

            return str_repeat('0', $length);
        };

        $stream = $this->getStreamProphecy($closureContents, $sizeBytes, $position, $peakBufferLength);
        $stream->isSeekable()->willReturn($seekable);
        $stream->isReadable()->willReturn($readable);

        $response = (new Response())
            ->withStatus(200)
            ->withBody($stream->reveal());


        if ($rangeBlocks) {
            $response = $response->withHeader('Content-Range', 'bytes ' . $first . '-' . $last . '/*');
        }

        ob_start(
            function () use (& $closureTrackMemoryUsage) {
                $closureTrackMemoryUsage();

                return '';
            },
            $maxBufferLength
        );

        gc_collect_cycles();

        gc_disable();

        $this->emitter->emit($response, $maxBufferLength);

        ob_end_flush();

        gc_enable();

        gc_collect_cycles();

        $localMemoryUsage = memory_get_usage();

        $this->assertLessThanOrEqual($maxBufferLength, $peakBufferLength);
        $this->assertLessThanOrEqual($maxAllowedMemoryUsage, $peakMemoryUsage - $localMemoryUsage);
    }

    public function contentRangeProvider()
    {
        return [
            ['bytes 0-2/*', 'Hello world', 'Hel'],
            ['bytes 3-6/*', 'Hello world', 'lo w'],
            ['items 0-0/1', 'Hello world', 'Hello world'],
        ];
    }

    public function emitJsonResponseProvider()
    {
        return [[0.1],
                ['test'],
                [true],
                [1],
                [['key1' => 'value1']],
                [null],
                [[[0.1, 0.2], ['test', 'test2'], [true, false], ['key1' => 'value1'], [null]]],
        ];
    }

    /**
     * @dataProvider emitJsonResponseProvider
     */
    public function testEmitJsonResponse($contents)
    {
        $response = (new JsonResponse($contents))
            ->withStatus(200);

        ob_start();
        $this->emitter->emit($response);
        $this->assertEquals(json_encode($contents), ob_get_clean());
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
