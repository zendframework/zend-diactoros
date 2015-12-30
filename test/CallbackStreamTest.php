<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Oscar Otero (http://oscarotero.com) / Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Diactoros\RelativeStream;
use Zend\Diactoros\CallbackStream;

/**
 * @covers \Zend\Diactoros\CallbackStream
 */
class CallbackStreamTest extends TestCase
{
    public function testToString()
    {
        $stream = new CallbackStream(function () {
            return 'foobarbaz';
        });

        $ret = $stream->__toString();
        $this->assertEquals('foobarbaz', $ret);
    }

    public function testClose()
    {
        $stream = new CallbackStream(function () {
        });

        $stream->close();

        $callback = $stream->detach();

        $this->assertNull($callback);
    }

    public function testDetach()
    {
        $callback = function () {
        };
        $stream = new CallbackStream($callback);
        $ret = $stream->detach();
        $this->assertSame($callback, $ret);
    }

    public function testEof()
    {
        $stream = new CallbackStream(function () {
        });
        $ret = $stream->eof();
        $this->assertFalse($ret);

        $stream->getContents();
        $ret = $stream->eof();
        $this->assertTrue($ret);
    }

    public function testGetSize()
    {
        $stream = new CallbackStream(function () {
        });
        $ret = $stream->getSize();
        $this->assertNull($ret);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testTell()
    {
        $stream = new CallbackStream(function () {
        });
        $stream->tell();
    }

    public function testIsSeekable()
    {
        $stream = new CallbackStream(function () {
        });
        $ret = $stream->isSeekable();
        $this->assertFalse($ret);
    }

    public function testIsWritable()
    {
        $stream = new CallbackStream(function () {
        });
        $ret = $stream->isWritable();
        $this->assertFalse($ret);
    }

    public function testIsReadable()
    {
        $stream = new CallbackStream(function () {
        });
        $ret = $stream->isReadable();
        $this->assertFalse($ret);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSeek()
    {
        $stream = new CallbackStream(function () {
        });
        $stream->seek(0);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRewind()
    {
        $stream = new CallbackStream(function () {
        });
        $stream->rewind();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWrite()
    {
        $stream = new CallbackStream(function () {
        });
        $stream->write('foobarbaz');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testRead()
    {
        $stream = new CallbackStream(function () {
        });
        $stream->read(3);
    }

    public function testGetContents()
    {
        $stream = new CallbackStream(function () {
            return 'foobarbaz';
        });

        $ret = $stream->getContents();
        $this->assertEquals('foobarbaz', $ret);
    }

    public function testGetMetadata()
    {
        $stream = new CallbackStream(function () {
        });

        $ret = $stream->getMetadata('stream_type');
        $this->assertEquals('callback', $ret);

        $ret = $stream->getMetadata('seekable');
        $this->assertFalse($ret);

        $ret = $stream->getMetadata('eof');
        $this->assertFalse($ret);
    }
}
