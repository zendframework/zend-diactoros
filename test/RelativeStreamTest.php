<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Diactoros\RelativeStream;
use Zend\Diactoros\Stream;

class RelativeStreamTest extends TestCase
{
    public function testToString()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->seek(100, SEEK_SET)->shouldBeCalled();
        $decorated->getContents()->shouldBeCalled()->willReturn('foobarbaz');

        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->__toString();
        $this->assertEquals('foobarbaz', $ret);
    }

    public function testClose()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->close()->shouldBeCalled();
        $stream = new RelativeStream($decorated->reveal(), 100);
        $stream->close();
    }

    public function testDetach()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->detach()->shouldBeCalled()->willReturn(250);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->detach();
        $this->assertEquals(250, $ret);
    }

    public function testGetSize()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->getSize()->shouldBeCalled()->willReturn(250);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->getSize();
        $this->assertEquals(150, $ret);
    }

    public function testTell()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->tell()->shouldBeCalled()->willReturn(188);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->tell();
        $this->assertEquals(88, $ret);
    }

    public function testIsSeekable()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->isSeekable()->shouldBeCalled()->willReturn(true);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->isSeekable();
        $this->assertEquals(true, $ret);
    }

    public function testIsWritable()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->isWritable()->shouldBeCalled()->willReturn(true);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->isWritable();
        $this->assertEquals(true, $ret);
    }

    public function testIsReadable()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->isReadable()->shouldBeCalled()->willReturn(false);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->isReadable();
        $this->assertEquals(false, $ret);
    }

    public function testSeek()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->seek(126, SEEK_SET)->shouldBeCalled()->willReturn(0);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->seek(26);
        $this->assertEquals(0, $ret);
    }

    public function testRewind()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->seek(100, SEEK_SET)->shouldBeCalled()->willReturn(0);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->rewind();
        $this->assertEquals(0, $ret);
    }

    public function testWrite()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->write("foobaz")->shouldBeCalled()->willReturn(6);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->write("foobaz");
        $this->assertEquals(6, $ret);
    }

    public function testRead()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->read(3)->shouldBeCalled()->willReturn("foo");
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->read(3);
        $this->assertEquals("foo", $ret);
    }

    public function testGetContents()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->getContents()->shouldBeCalled()->willReturn("foo");
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->getContents();
        $this->assertEquals("foo", $ret);
    }

    public function testGetMetadata()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->getMetadata("bar")->shouldBeCalled()->willReturn("foo");
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->getMetadata("bar");
        $this->assertEquals("foo", $ret);
    }
}
