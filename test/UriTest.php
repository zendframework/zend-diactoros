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
        $this->assertEquals('user:pass', $uri->getAuthority());
        $this->assertEquals('local.example.com', $uri->getHost());
        $this->assertEquals(3001, $uri->getPort());
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

    public function testWithAuthorityReturnsNewInstanceWithProvidedUser()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withAuthority('matthew');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('matthew', $new->getAuthority());
        $this->assertEquals('https://matthew@local.example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testWithAuthorityReturnsNewInstanceWithProvidedUserAndPassword()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withAuthority('matthew', 'zf2');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('matthew:zf2', $new->getAuthority());
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

    public function validOriginForm()
    {
        return [
            'path-only'         => [ '/foo/bar' ],
            'path-and-query'    => [ '/foo/bar?baz=bat' ],
            'non-prefixed-path' => [ 'foo/bar' ],
        ];
    }

    /**
     * @dataProvider validOriginForm
     */
    public function testValidOriginFormsReturnTrueWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertTrue($uri->isOriginForm());
    }

    public function invalidOriginForm()
    {
        return [
            'scheme-and-host'            => [ 'http://example.com' ],
            'scheme-host-and-path'       => [ 'http://example.com/foo' ],
            'scheme-host-path-and-query' => [ 'http://example.com/foo?bar=baz' ],
        ];
    }

    /**
     * @dataProvider invalidOriginForm
     */
    public function testInvalidOriginFormsReturnFalseWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertFalse($uri->isOriginForm());
    }

    public function validAbsoluteForm()
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
     * @dataProvider validAbsoluteForm
     */
    public function testValidAbsoluteFormsReturnTrueWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertTrue($uri->isAbsoluteForm());
    }

    /**
     * @dataProvider validOriginForm
     */
    public function testInvalidAbsoluteFormsReturnFalseWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertFalse($uri->isAbsoluteForm());
    }

    public function validAuthorityForm()
    {
        return [
            'scheme-authority-host'                 => [ 'http://user@example.com' ],
            'scheme-authority-host-path'            => [ 'http://user@example.com/foo' ],
            'scheme-authority-host-port'            => [ 'http://user@example.com:3000' ],
            'scheme-authority-host-port-path'       => [ 'http://user@example.com:3000/foo' ],
            'scheme-authority-host-path-query'      => [ 'http://user@example.com/foo?bar=baz' ],
            'scheme-authority-host-port-path-query' => [ 'http://user@example.com:3000/foo?bar=baz' ],
        ];
    }

    /**
     * @dataProvider validAuthorityForm
     */
    public function testValidAuthorityFormsReturnTrueWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertTrue($uri->isAuthorityForm());
    }

    public function invalidAuthorityForm()
    {
        return array_merge($this->validOriginForm(), [
            'scheme-host'                           => [ 'http://example.com' ],
            'scheme-host-port'                      => [ 'http://example.com:3000' ],
            'scheme-host-path'                      => [ 'http://example.com/foo' ],
            'scheme-host-port-path'                 => [ 'http://example.com:3000/foo' ],
            'scheme-host-path-query'                => [ 'http://example.com/foo?bar=baz' ],
            'scheme-host-port-path-query'           => [ 'http://example.com:3000/foo?bar=baz' ],
        ]);
    }

    /**
     * @dataProvider invalidAuthorityForm
     */
    public function testInvalidAuthorityFormsReturnFalseWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertFalse($uri->isAuthorityForm());
    }

    public function testValidAsterixFormsReturnTrueWhenTested()
    {
        $uri = new Uri('*');
        $this->assertTrue($uri->isAsterixForm());
    }

    public function invalidAsterixForm()
    {
        return array_merge(
            $this->validOriginForm(),
            $this->validAbsoluteForm()
        );
    }

    /**
     * @dataProvider invalidAsterixForm
     */
    public function testInvalidAsterixFormsReturnFalseWhenTested($url)
    {
        $uri = new Uri($url);
        $this->assertFalse($uri->isAsterixForm());
    }
}
