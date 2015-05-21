<?php
namespace PhlyTest\Http;

use Phly\Http\Stream;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;

class StreamTest extends TestCase
{
    public $tmpnam;

    public function setUp()
    {
        $this->tmpnam = null;
        $this->stream = new Stream('php://memory', 'wb+');
    }

    public function tearDown()
    {
        if ($this->tmpnam && file_exists($this->tmpnam)) {
            unlink($this->tmpnam);
        }
    }

    public function testCanInstantiateWithStreamIdentifier()
    {
        $this->assertInstanceOf('Phly\Http\Stream', $this->stream);
    }

    public function testCanInstantiteWithStreamResource()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream   = new Stream($resource);
        $this->assertInstanceOf('Phly\Http\Stream', $stream);
    }

    public function testIsReadableReturnsFalseIfStreamIsNotReadable()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        $stream = new Stream($this->tmpnam, 'w');
        $this->assertFalse($stream->isReadable());
    }

    public function testIsWritableReturnsFalseIfStreamIsNotWritable()
    {
        $stream = new Stream('php://memory', 'r');
        $this->assertFalse($stream->isWritable());
    }

    public function testToStringRetrievesFullContentsOfStream()
    {
        $message = 'foo bar';
        $this->stream->write($message);
        $this->assertEquals($message, (string) $this->stream);
    }

    public function testDetachReturnsResource()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream   = new Stream($resource);
        $this->assertSame($resource, $stream->detach());
    }

    public function testPassingInvalidStreamResourceToConstructorRaisesException()
    {
        $this->setExpectedException('InvalidArgumentException');
        $stream = new Stream(['  THIS WILL NOT WORK  ']);
    }

    public function testStringSerializationReturnsEmptyStringWhenStreamIsNotReadable()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $stream = new Stream($this->tmpnam, 'w');

        $this->assertEquals('', $stream->__toString());
    }

    public function testCloseClosesResource()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $stream->close();
        $this->assertFalse(is_resource($resource));
    }

    public function testCloseUnsetsResource()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $stream->close();

        $this->assertNull($stream->detach());
    }

    public function testCloseDoesNothingAfterDetach()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $detached = $stream->detach();

        $stream->close();
        $this->assertTrue(is_resource($detached));
        $this->assertSame($resource, $detached);
    }

    /**
     * @group 42
     */
    public function testSizeReportsNullWhenNoResourcePresent()
    {
        $this->stream->detach();
        $this->assertNull($this->stream->getSize());
    }

    public function testTellReportsCurrentPositionInResource()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);

        $this->assertEquals(2, $stream->tell());
    }

    public function testTellRaisesExceptionIfResourceIsDetached()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        $stream->detach();
        $this->setExpectedException('RuntimeException', 'No resource');
        $stream->tell();
    }

    public function testEofReportsFalseWhenNotAtEndOfStream()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        $this->assertFalse($stream->eof());
    }

    public function testEofReportsTrueWhenAtEndOfStream()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);

        while (! feof($resource)) {
            fread($resource, 4096);
        }
        $this->assertTrue($stream->eof());
    }

    public function testEofReportsTrueWhenStreamIsDetached()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        $stream->detach();
        $this->assertTrue($stream->eof());
    }

    public function testIsSeekableReturnsTrueForReadableStreams()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $this->assertTrue($stream->isSeekable());
    }

    public function testIsSeekableReturnsFalseForDetachedStreams()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->assertFalse($stream->isSeekable());
    }

    public function testSeekAdvancesToGivenOffsetOfStream()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $this->assertTrue($stream->seek(2));
        $this->assertEquals(2, $stream->tell());
    }

    public function testRewindResetsToStartOfStream()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $this->assertTrue($stream->seek(2));
        $stream->rewind();
        $this->assertEquals(0, $stream->tell());
    }

    public function testSeekRaisesExceptionWhenStreamIsDetached()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->setExpectedException('RuntimeException', 'No resource');
        $stream->seek(2);
    }

    public function testIsWritableReturnsFalseWhenStreamIsDetached()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->assertFalse($stream->isWritable());
    }

    public function testWriteRaisesExceptionWhenStreamIsDetached()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->setExpectedException('RuntimeException', 'No resource');
        $stream->write('bar');
    }

    public function testIsReadableReturnsFalseWhenStreamIsDetached()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->assertFalse($stream->isReadable());
    }

    public function testReadRaisesExceptionWhenStreamIsDetached()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'r');
        $stream = new Stream($resource);
        $stream->detach();
        $this->setExpectedException('RuntimeException', 'No resource');
        $stream->read(4096);
    }

    public function testReadReturnsEmptyStringWhenAtEndOfFile()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'r');
        $stream = new Stream($resource);
        while (! feof($resource)) {
            fread($resource, 4096);
        }
        $this->assertEquals('', $stream->read(4096));
    }

    public function testGetContentsReturnsEmptyStringIfStreamIsNotReadable()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'phly');
        file_put_contents($this->tmpnam, 'FOO BAR');
        $resource = fopen($this->tmpnam, 'w');
        $stream = new Stream($resource);
        $this->assertEquals('', $stream->getContents());
    }

    public function invalidResources()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'PHLY');
        return [
            'null' => [ null ],
            'false' => [ false ],
            'true' => [ true ],
            'int' => [ 1 ],
            'float' => [ 1.1 ],
            'string-non-resource' => [ 'foo-bar-baz' ],
            'array' => [ [ fopen($this->tmpnam, 'r+') ] ],
            'object' => [ (object) [ 'resource' => fopen($this->tmpnam, 'r+') ] ],
        ];
    }

    /**
     * @dataProvider invalidResources
     */
    public function testAttachWithNonStringNonResourceRaisesException($resource)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid stream');
        $this->stream->attach($resource);
    }

    public function testAttachWithResourceAttachesResource()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'PHLY');
        $resource = fopen($this->tmpnam, 'r+');
        $this->stream->attach($resource);

        $r = new ReflectionProperty($this->stream, 'resource');
        $r->setAccessible(true);
        $test = $r->getValue($this->stream);
        $this->assertSame($resource, $test);
    }

    public function testAttachWithStringRepresentingResourceCreatesAndAttachesResource()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'PHLY');
        $this->stream->attach($this->tmpnam);

        $resource = fopen($this->tmpnam, 'r+');
        fwrite($resource, 'FooBar');

        $this->stream->rewind();
        $test = (string) $this->stream;
        $this->assertEquals('FooBar', $test);
    }

    public function testGetContentsShouldGetFullStreamContents()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'PHLY');
        $resource = fopen($this->tmpnam, 'r+');
        $this->stream->attach($resource);

        fwrite($resource, 'FooBar');

        // rewind, because current pointer is at end of stream!
        $this->stream->rewind();
        $test = $this->stream->getContents();
        $this->assertEquals('FooBar', $test);
    }

    public function testGetContentsShouldReturnStreamContentsFromCurrentPointer()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'PHLY');
        $resource = fopen($this->tmpnam, 'r+');
        $this->stream->attach($resource);

        fwrite($resource, 'FooBar');

        // seek to position 3
        $this->stream->seek(3);
        $test = $this->stream->getContents();
        $this->assertEquals('Bar', $test);
    }

    public function testGetMetadataReturnsAllMetadataWhenNoKeyPresent()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'PHLY');
        $resource = fopen($this->tmpnam, 'r+');
        $this->stream->attach($resource);

        $expected = stream_get_meta_data($resource);
        $test     = $this->stream->getMetadata();

        $this->assertEquals($expected, $test);
    }

    public function testGetMetadataReturnsDataForSpecifiedKey()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'PHLY');
        $resource = fopen($this->tmpnam, 'r+');
        $this->stream->attach($resource);

        $metadata = stream_get_meta_data($resource);
        $expected = $metadata['uri'];

        $test     = $this->stream->getMetadata('uri');

        $this->assertEquals($expected, $test);
    }

    public function testGetMetadataReturnsNullIfNoDataExistsForKey()
    {
        $this->tmpnam = tempnam(sys_get_temp_dir(), 'PHLY');
        $resource = fopen($this->tmpnam, 'r+');
        $this->stream->attach($resource);

        $this->assertNull($this->stream->getMetadata('TOTALLY_MADE_UP'));
    }

    /**
     * @group 42
     */
    public function testGetSizeReturnsStreamSize()
    {
        $resource = fopen(__FILE__, 'r');
        $expected = fstat($resource);
        $stream = new Stream($resource);
        $this->assertEquals($expected['size'], $stream->getSize());
    }
}
