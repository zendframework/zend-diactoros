<?php
namespace PhlyTest\Http;

use Phly\Http\Uri;
use PHPUnit_Framework_TestCase as TestCase;

class UriTest extends TestCase
{
    public function testConstructorSetsAllProperties()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('user:pass', $uri->getUserInfo());
        $this->assertEquals('local.example.com', $uri->getHost());
        $this->assertEquals(3001, $uri->getPort());
        $this->assertEquals('user:pass@local.example.com:3001', $uri->getAuthority());
        $this->assertEquals('/foo', $uri->getPath());
        $this->assertEquals('bar=baz', $uri->getQuery());
        $this->assertEquals('quz', $uri->getFragment());
    }

    public function testCanSerializeToString()
    {
        $url = 'https://user:pass@local.example.com:3001/foo?bar=baz#quz';
        $uri = new Uri($url);
        $this->assertEquals($url, (string) $uri);
    }

    public function testWithSchemeReturnsNewInstanceWithNewScheme()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withScheme('http');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('http', $new->getScheme());
        $this->assertEquals('http://user:pass@local.example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testWithUserInfoReturnsNewInstanceWithProvidedUser()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('matthew');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('matthew', $new->getUserInfo());
        $this->assertEquals('https://matthew@local.example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testWithUserInfoReturnsNewInstanceWithProvidedUserAndPassword()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('matthew', 'zf2');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('matthew:zf2', $new->getUserInfo());
        $this->assertEquals('https://matthew:zf2@local.example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testWithHostReturnsNewInstanceWithProvidedHost()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withHost('framework.zend.com');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('framework.zend.com', $new->getHost());
        $this->assertEquals('https://user:pass@framework.zend.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testWithPortReturnsNewInstanceWithProvidedPort()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPort(3000);
        $this->assertNotSame($uri, $new);
        $this->assertEquals(3000, $new->getPort());
        $this->assertEquals('https://user:pass@local.example.com:3000/foo?bar=baz#quz', (string) $new);
    }

    public function invalidPorts()
    {
        return [
            'null'      => [ null ],
            'true'      => [ true ],
            'false'     => [ false ],
            'string'    => [ 'string' ],
            'array'     => [ [ 3000 ] ],
            'object'    => [ (object) [ 3000 ] ],
            'zero'      => [ 0 ],
            'too-small' => [ -1 ],
            'too-big'   => [ 65536 ],
        ];
    }

    /**
     * @dataProvider invalidPorts
     */
    public function testWithPortRaisesExceptionForInvalidPorts($port)
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->setExpectedException('InvalidArgumentException', 'Invalid port');
        $new = $uri->withPort($port);
    }

    public function testWithPathReturnsNewInstanceWithProvidedPath()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPath('/bar/baz');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('/bar/baz', $new->getPath());
        $this->assertEquals('https://user:pass@local.example.com:3001/bar/baz?bar=baz#quz', (string) $new);
    }

    public function invalidPaths()
    {
        return [
            'null'      => [ null ],
            'true'      => [ true ],
            'false'     => [ false ],
            'array'     => [ [ '/bar/baz' ] ],
            'object'    => [ (object) [ '/bar/baz' ] ],
            'query'     => [ '/bar/baz?bat=quz' ],
            'fragment'  => [ '/bar/baz#bat' ],
        ];
    }

    /**
     * @dataProvider invalidPaths
     */
    public function testWithPathRaisesExceptionForInvalidPaths($path)
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->setExpectedException('InvalidArgumentException', 'Invalid path');
        $new = $uri->withPath($path);
    }

    public function testWithQueryReturnsNewInstanceWithProvidedQuery()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withQuery('baz=bat');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('baz=bat', $new->getQuery());
        $this->assertEquals('https://user:pass@local.example.com:3001/foo?baz=bat#quz', (string) $new);
    }

    public function invalidQueryStrings()
    {
        return [
            'null'      => [ null ],
            'true'      => [ true ],
            'false'     => [ false ],
            'array'     => [ [ 'baz=bat' ] ],
            'object'    => [ (object) [ 'baz=bat' ] ],
            'fragment'  => [ 'baz=bat#quz' ],
        ];
    }

    /**
     * @dataProvider invalidQueryStrings
     */
    public function testWithQueryRaisesExceptionForInvalidQueryStrings($query)
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $this->setExpectedException('InvalidArgumentException', 'Query string');
        $new = $uri->withQuery($query);
    }

    public function testWithFragmentReturnsNewInstanceWithProvidedFragment()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withFragment('qat');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('qat', $new->getFragment());
        $this->assertEquals('https://user:pass@local.example.com:3001/foo?bar=baz#qat', (string) $new);
    }

    public function validOrigins()
    {
        return [
            'path-only'         => [ '/foo/bar' ],
            'path-and-query'    => [ '/foo/bar?baz=bat' ],
            'non-prefixed-path' => [ 'foo/bar' ],
        ];
    }

    /**
     * @dataProvider validOrigins
     */
    public function testValidOriginsReturnTrueWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertTrue($uri->isOrigin());
    }

    public function invalidOrigins()
    {
        return [
            'scheme-and-host'            => [ 'http://example.com' ],
            'scheme-host-and-path'       => [ 'http://example.com/foo' ],
            'scheme-host-path-and-query' => [ 'http://example.com/foo?bar=baz' ],
        ];
    }

    /**
     * @dataProvider invalidOrigins
     */
    public function testInvalidOriginsReturnFalseWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertFalse($uri->isOrigin());
    }

    public function validAbsolute()
    {
        return [
            'scheme-host'                           => [ 'http://example.com' ],
            'scheme-host-port'                      => [ 'http://example.com:3000' ],
            'scheme-host-path'                      => [ 'http://example.com/foo' ],
            'scheme-host-port-path'                 => [ 'http://example.com:3000/foo' ],
            'scheme-host-path-query'                => [ 'http://example.com/foo?bar=baz' ],
            'scheme-host-port-path-query'           => [ 'http://example.com:3000/foo?bar=baz' ],
            'scheme-authority-host'                 => [ 'http://user@example.com' ],
            'scheme-authority-host-path'            => [ 'http://user@example.com/foo' ],
            'scheme-authority-host-port'            => [ 'http://user@example.com:3000' ],
            'scheme-authority-host-port-path'       => [ 'http://user@example.com:3000/foo' ],
            'scheme-authority-host-path-query'      => [ 'http://user@example.com/foo?bar=baz' ],
            'scheme-authority-host-port-path-query' => [ 'http://user@example.com:3000/foo?bar=baz' ],
        ];
    }

    /**
     * @dataProvider validAbsolute
     */
    public function testValidAbsoluteReturnTrueWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertTrue($uri->isAbsolute());
    }

    /**
     * @dataProvider validOrigins
     */
    public function testInvalidAbsoluteReturnFalseWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertFalse($uri->isAbsolute());
    }

    public function validAuthority()
    {
        return [
            'host'           => [ 'http://example.com' ],
            'host-port'      => [ 'http://example.com:3000' ],
            'user-host'      => [ 'http://user@example.com' ],
            'user-host-port' => [ 'http://user@example.com:3000' ],
        ];
    }

    /**
     * @dataProvider validAuthority
     */
    public function testValidAuthorityReturnsTrueWhenTested($url)
    {
        $uri = new Uri($url);
        $uri = $uri->withScheme('');
        $this->assertTrue($uri->isAuthority());
    }

    public function invalidAuthority()
    {
        return array_merge($this->validOrigins(), [
            'scheme-host'                           => [ 'http://example.com' ],
            'scheme-host-port'                      => [ 'http://example.com:3000' ],
            'scheme-host-path'                      => [ 'http://example.com/foo' ],
            'scheme-host-port-path'                 => [ 'http://example.com:3000/foo' ],
            'scheme-host-path-query'                => [ 'http://example.com/foo?bar=baz' ],
            'scheme-host-port-path-query'           => [ 'http://example.com:3000/foo?bar=baz' ],
        ]);
    }

    /**
     * @dataProvider invalidAuthority
     */
    public function testInvalidAuthorityReturnsFalseWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertFalse($uri->isAuthority());
    }

    public function testValidAsteriskReturnTrueWhenTested()
    {
        $uri = new Uri('*');
        $this->assertTrue($uri->isAsterisk());
    }

    public function invalidAsterisk()
    {
        return array_merge(
            $this->validOrigins(),
            $this->validAbsolute()
        );
    }

    /**
     * @dataProvider invalidAsterisk
     */
    public function testInvalidAsteriskReturnFalseWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertFalse($uri->isAsterisk());
    }

    public function authorityInfo()
    {
        return [
            'host-only'      => [ 'http://foo.com/bar',         'foo.com' ],
            'host-port'      => [ 'http://foo.com:3000/bar',    'foo.com:3000' ],
            'user-host'      => [ 'http://me@foo.com/bar',      'me@foo.com' ],
            'user-host-port' => [ 'http://me@foo.com:3000/bar', 'me@foo.com:3000' ],
        ];
    }

    /**
     * @dataProvider authorityInfo
     */
    public function testRetrievingAuthorityReturnsExpectedValues($url, $expected)
    {
        $uri = new Uri($url);
        $this->assertEquals($expected, $uri->getAuthority());
    }

    public function testCanEmitOriginFormUrl()
    {
        $url = '/foo/bar?baz=bat';
        $uri = new Uri($url);
        $this->assertEquals($url, (string) $uri);
    }

    public function testCanEmitAuthorityFormUrl()
    {
        $authority = 'me:too@example.com:3000';
        $uri = ( new Uri() )
            ->withUserInfo('me', 'too')
            ->withHost('example.com')
            ->withPort(3000);
        $this->assertEquals($authority, (string) $uri);
    }

    public function testCanEmitAsteriskFormUrl()
    {
        $uri = new Uri('*');
        $this->assertEquals('*', (string) $uri);
    }
}
