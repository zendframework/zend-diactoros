<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use RuntimeException;
use Zend\Diactoros\RelativeStream;
use Zend\Diactoros\Stream;

use const SEEK_SET;

/**
 * @covers \Zend\Diactoros\RelativeStream
 */
class RelativeStreamTest extends TestCase
{
    public function testToString()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->isSeekable()->willReturn(true);
        $decorated->tell()->willReturn(100);
        $decorated->seek(100, SEEK_SET)->shouldBeCalled();
        $decorated->getContents()->shouldBeCalled()->willReturn('foobarbaz');

        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->__toString();
        $this->assertSame('foobarbaz', $ret);
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
        $this->assertSame(250, $ret);
    }

    public function testGetSize()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->getSize()->shouldBeCalled()->willReturn(250);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->getSize();
        $this->assertSame(150, $ret);
    }

    public function testTell()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->tell()->shouldBeCalled()->willReturn(188);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->tell();
        $this->assertSame(88, $ret);
    }

    public function testIsSeekable()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->isSeekable()->shouldBeCalled()->willReturn(true);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->isSeekable();
        $this->assertSame(true, $ret);
    }

    public function testIsWritable()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->isWritable()->shouldBeCalled()->willReturn(true);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->isWritable();
        $this->assertSame(true, $ret);
    }

    public function testIsReadable()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->isReadable()->shouldBeCalled()->willReturn(false);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->isReadable();
        $this->assertSame(false, $ret);
    }

    public function testSeek()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->seek(126, SEEK_SET)->shouldBeCalled();
        $stream = new RelativeStream($decorated->reveal(), 100);
        $this->assertNull($stream->seek(26));
    }

    public function testRewind()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->seek(100, SEEK_SET)->shouldBeCalled();
        $stream = new RelativeStream($decorated->reveal(), 100);
        $this->assertNull($stream->rewind());
    }

    public function testWrite()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->tell()->willReturn(100);
        $decorated->write("foobaz")->shouldBeCalled()->willReturn(6);
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->write("foobaz");
        $this->assertSame(6, $ret);
    }

    public function testRead()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->tell()->willReturn(100);
        $decorated->read(3)->shouldBeCalled()->willReturn("foo");
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->read(3);
        $this->assertSame("foo", $ret);
    }

    public function testGetContents()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->tell()->willReturn(100);
        $decorated->getContents()->shouldBeCalled()->willReturn("foo");
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->getContents();
        $this->assertSame("foo", $ret);
    }

    public function testGetMetadata()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->getMetadata("bar")->shouldBeCalled()->willReturn("foo");
        $stream = new RelativeStream($decorated->reveal(), 100);
        $ret = $stream->getMetadata("bar");
        $this->assertSame("foo", $ret);
    }

    public function testWriteRaisesExceptionWhenPointerIsBehindOffset()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->tell()->shouldBeCalled()->willReturn(0);
        $decorated->write("foobaz")->shouldNotBeCalled();
        $stream = new RelativeStream($decorated->reveal(), 100);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid pointer position');

        $stream->write("foobaz");
    }

    public function testReadRaisesExceptionWhenPointerIsBehindOffset()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->tell()->shouldBeCalled()->willReturn(0);
        $decorated->read(3)->shouldNotBeCalled();
        $stream = new RelativeStream($decorated->reveal(), 100);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid pointer position');

        $stream->read(3);
    }

    public function testGetContentsRaisesExceptionWhenPointerIsBehindOffset()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->tell()->shouldBeCalled()->willReturn(0);
        $decorated->getContents()->shouldNotBeCalled();
        $stream = new RelativeStream($decorated->reveal(), 100);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid pointer position');

        $stream->getContents();
    }

    public function testCanReadContentFromNotSeekableResource()
    {
        $decorated = $this->prophesize(Stream::class);
        $decorated->isSeekable()->willReturn(false);
        $decorated->seek(Argument::any())->shouldNotBeCalled();
        $decorated->tell()->willReturn(3);
        $decorated->getContents()->willReturn('CONTENTS');

        $stream = new RelativeStream($decorated->reveal(), 3);
        $this->assertSame('CONTENTS', $stream->__toString());
    }
}
