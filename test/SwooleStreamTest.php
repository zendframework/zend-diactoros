<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use swoole_http_request;
use Zend\Diactoros\SwooleStream;

class SwooleStreamTest extends TestCase
{
    const DEFAULT_CONTENT = 'This is a test!';

    public function setUp()
    {
        $this->request = $this->prophesize(swoole_http_request::class);
        $this->request
            ->rawcontent()
            ->willReturn(self::DEFAULT_CONTENT);

        $this->stream = new SwooleStream($this->request->reveal());
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(StreamInterface::class, $this->stream);
    }

    public function testGetContents()
    {
        $this->assertEquals(self::DEFAULT_CONTENT, $this->stream->getContents());
    }

    public function testGetContentsWithEmptyBody()
    {
        $this->request
            ->rawcontent()
            ->willReturn('');
        $this->stream = new SwooleStream($this->request->reveal());

        $this->assertEquals('', $this->stream->getContents());
    }

    public function testToString()
    {
        $this->assertEquals(self::DEFAULT_CONTENT, (string) $this->stream);
    }

    public function testGetSize()
    {
        $this->assertEquals(
            strlen(self::DEFAULT_CONTENT),
            $this->stream->getSize()
        );
    }

    public function testGetSizeWithEmptyBody()
    {
        $this->request
            ->rawcontent()
            ->willReturn('');
        $this->stream = new SwooleStream($this->request->reveal());

        $this->assertEquals(0, $this->stream->getSize());
    }

    public function testTell()
    {
        $tot = strlen(self::DEFAULT_CONTENT);
        for ($i = 0; $i < strlen(self::DEFAULT_CONTENT); $i++) {
            $this->stream->seek($i);
            $this->assertEquals($i, $this->stream->tell());
        }
    }

    public function testEof()
    {
        $this->assertFalse($this->stream->eof());
        $this->stream->seek($this->stream->getSize() - 1);
        $this->assertTrue($this->stream->eof());
    }

    public function testIsReadable()
    {
        $this->assertTrue($this->stream->isReadable());
    }

    public function testRead()
    {
        $result = $this->stream->read(4);
        $this->assertEquals(substr(self::DEFAULT_CONTENT, 0, 4), $result);
        $this->assertEquals(4, $this->stream->tell());
    }

    public function testIsSeekable()
    {
        $this->assertTrue($this->stream->isSeekable());
    }

    public function testSeek()
    {
        $this->stream->seek(4);
        $this->assertEquals(4, $this->stream->tell());
        $this->stream->seek(1, SEEK_CUR);
        $this->assertEquals(5, $this->stream->tell());
        $this->stream->seek(-1, SEEK_END);
        $this->assertEquals(strlen(self::DEFAULT_CONTENT) - 2, $this->stream->tell());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Offset cannot be longer than content size
     */
    public function testSeekSetOverflow()
    {
        $this->stream->seek(strlen(self::DEFAULT_CONTENT));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Offset + current position cannot be longer than content size
     */
    public function testSeekCurOverflow()
    {
        $this->stream->seek(strlen(self::DEFAULT_CONTENT), SEEK_CUR);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Offset must be a negative number to be under the content size
     */
    public function testSeekEndOverflow()
    {
        $this->stream->seek(1, SEEK_END);
    }

    public function testRewind()
    {
        $this->stream->rewind();
        $this->assertEquals(0, $this->stream->tell());
    }

    public function testIsWritable()
    {
        $this->assertFalse($this->stream->isWritable());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Stream is not writable
     */
    public function testWrite()
    {
        $this->stream->write('Hello!');
    }

    public function testGetMetadata()
    {
        $this->assertNull($this->stream->getMetadata());
    }

    public function testDetach()
    {
        $this->assertNull($this->stream->detach());
    }

    public function testClose()
    {
        $this->assertNull($this->stream->close());
    }
}
