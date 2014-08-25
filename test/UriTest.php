<?php
namespace PhlyTest\Http;

use Phly\Http\Uri;
use PHPUnit_Framework_TestCase as TestCase;

class UriTest extends TestCase
{
    public function uriFragmentsAndRelatedStrings()
    {
        return [
            'nothing' => [[], 'http://'],
            'scheme-only' => [[
                'scheme' => 'file',
            ], 'file://'],
            'host-only' => [[
                'host' => 'localhost',
            ], 'http://localhost'],
            'host-and-port' => [[
                'host' => 'localhost',
                'port' => 3001,
            ], 'http://localhost:3001'],
            'host-and-port-80' => [[
                'host' => 'localhost',
                'port' => 80,
            ], 'http://localhost'],
            'https-host-and-port' => [[
                'scheme' => 'https',
                'host' => 'localhost',
                'port' => 3001,
            ], 'https://localhost:3001'],
            'https-host-and-port-443' => [[
                'scheme' => 'https',
                'host' => 'localhost',
                'port' => 443,
            ], 'https://localhost'],
            'host-and-path' => [[
                'host' => 'localhost',
                'path' => '/foo/bar',
            ], 'http://localhost/foo/bar'],
            'host-and-path-no-leading-slash' => [[
                'host' => 'localhost',
                'path' => 'foo/bar',
            ], 'http://localhost/foo/bar'],
            'host-no-path-query' => [[
                'host' => 'localhost',
                'query' => 'foo=bar',
            ], 'http://localhost?foo=bar'],
            'host-no-path-fragment' => [[
                'host' => 'localhost',
                'fragment' => 'foo',
            ], 'http://localhost#foo'],
            'host-path-query' => [[
                'host' => 'localhost',
                'path' => '/path',
                'query' => 'foo=bar',
            ], 'http://localhost/path?foo=bar'],
            'host-path-fragment' => [[
                'host' => 'localhost',
                'path' => '/path',
                'fragment' => 'foo',
            ], 'http://localhost/path#foo'],
            'host-path-query-fragment' => [[
                'host' => 'localhost',
                'path' => '/path',
                'query' => 'foo=bar',
                'fragment' => 'foo',
            ], 'http://localhost/path?foo=bar#foo'],
        ];
    }

    /**
     * @dataProvider uriFragmentsAndRelatedStrings
     */
    public function testUriCreationFromArray($parts, $expected)
    {
        $this->assertEquals($expected, Uri::fromArray($parts)->uri);
    }

    public function testConstructorDoesNotSetPropertiesIfUriIsInvalid()
    {
        $uri = new Uri('this is bogus');
        $this->assertFalse($uri->isValid());

        foreach (['scheme', 'host', 'port', 'path', 'query', 'fragment'] as $part) {
            $this->assertNull($uri->{$part});
        }
    }

    public function testConstructorSetsAllPropertiesWhenValid()
    {
        $uri = new Uri('https://local.example.com:3001/foo?bar=baz#quz');
        $this->assertTrue($uri->isValid());
        $this->assertEquals('https', $uri->scheme);
        $this->assertEquals('local.example.com', $uri->host);
        $this->assertEquals(3001, $uri->port);
        $this->assertEquals('/foo', $uri->path);
        $this->assertEquals('bar=baz', $uri->query);
        $this->assertEquals('quz', $uri->fragment);
    }

    public function testCanSerializeToString()
    {
        $url = 'https://local.example.com:3001/foo?bar=baz#quz';
        $uri = new Uri($url);
        $this->assertEquals($url, (string) $uri);
    }

    public function testSetPathReturnsClone()
    {
        $url = 'https://local.example.com:3001/foo?bar=baz#quz';
        $uri = new Uri($url);
        $new = $uri->setPath('/bar');
        $this->assertNotSame($uri, $new);
    }

    public function testCloneReturnedFromSetPathContainsNewPath()
    {
        $url = 'https://local.example.com:3001/foo?bar=baz#quz';
        $uri = new Uri($url);
        $new = $uri->setPath('/bar');
        $this->assertEquals('/bar', $new->path);
        $this->assertEquals('/foo', $uri->path);
    }
}
