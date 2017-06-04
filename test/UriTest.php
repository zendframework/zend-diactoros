<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Diactoros;

use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Uri;

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

    public function testWithSchemeReturnsNewInstanceWithSameScheme()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withScheme('https');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('https', $new->getScheme());
        $this->assertEquals('https://user:pass@local.example.com:3001/foo?bar=baz#quz', (string) $new);
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

    public function testWithUserInfoThrowExceptionIfPasswordIsNotString()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');

        $this->expectException('InvalidArgumentException');
        $uri->withUserInfo('matthew', 1);
    }

    public function testWithUserInfoReturnsNewInstanceIfUserAndPasswordAreSameAsBefore()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('user', 'pass');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('user:pass', $new->getUserInfo());
        $this->assertEquals('https://user:pass@local.example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testWithHostReturnsNewInstanceWithProvidedHost()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withHost('framework.zend.com');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('framework.zend.com', $new->getHost());
        $this->assertEquals('https://user:pass@framework.zend.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testWithHostReturnsNewInstanceWithProvidedHostIsSameAsBefore()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withHost('local.example.com');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('local.example.com', $new->getHost());
        $this->assertEquals('https://user:pass@local.example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function validPorts()
    {
        return [
            'null'         => [ null ],
            'int'          => [ 3000 ],
            'string'       => [ "3000" ],
            'sameasbefore' => [ "3001" ],
        ];
    }

    /**
     * @dataProvider validPorts
     */
    public function testWithPortReturnsNewInstanceWithProvidedPort($port)
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPort($port);
        $this->assertNotSame($uri, $new);
        $this->assertEquals($port, $new->getPort());
        $this->assertEquals(
            sprintf('https://user:pass@local.example.com%s/foo?bar=baz#quz', $port === null ? '' : ':' . $port),
            (string) $new
        );
    }

    public function invalidPorts()
    {
        return [
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
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid port');
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

    public function testWithPathReturnsNewInstanceWithProvidedPathSameAsBefore()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPath('/foo');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('/foo', $new->getPath());
        $this->assertEquals('https://user:pass@local.example.com:3001/foo?bar=baz#quz', (string) $new);
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
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid path');
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
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Query string');
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

    public function testWithFragmentReturnsNewInstanceWithProvidedFragmentSameAsBefore()
    {
        $uri = new Uri('https://user:pass@local.example.com:3001/foo?bar=baz#quz');
        $new = $uri->withFragment('quz');
        $this->assertNotSame($uri, $new);
        $this->assertEquals('quz', $new->getFragment());
        $this->assertEquals('https://user:pass@local.example.com:3001/foo?bar=baz#quz', (string) $new);
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

    public function testSettingEmptyPathOnAbsoluteUriReturnsAnEmptyPath()
    {
        $uri = new Uri('http://example.com/foo');
        $new = $uri->withPath('');
        $this->assertEquals('', $new->getPath());
    }

    public function testStringRepresentationOfAbsoluteUriWithNoPathSetsAnEmptyPath()
    {
        $uri = new Uri('http://example.com');
        $this->assertEquals('http://example.com', (string) $uri);
    }

    public function testEmptyPathOnOriginFormRemainsAnEmptyPath()
    {
        $uri = new Uri('?foo=bar');
        $this->assertEquals('', $uri->getPath());
    }

    public function testStringRepresentationOfOriginFormWithNoPathRetainsEmptyPath()
    {
        $uri = new Uri('?foo=bar');
        $this->assertEquals('?foo=bar', (string) $uri);
    }

    public function invalidConstructorUris()
    {
        return [
            'null' => [ null ],
            'true' => [ true ],
            'false' => [ false ],
            'int' => [ 1 ],
            'float' => [ 1.1 ],
            'array' => [ [ 'http://example.com/' ] ],
            'object' => [ (object) [ 'uri' => 'http://example.com/' ] ],
        ];
    }

    /**
     * @dataProvider invalidConstructorUris
     */
    public function testConstructorRaisesExceptionForNonStringURI($uri)
    {
        $this->expectException('InvalidArgumentException');
        new Uri($uri);
    }

    public function testConstructorRaisesExceptionForSeriouslyMalformedURI()
    {
        $this->expectException('InvalidArgumentException');
        new Uri('http:///www.php-fig.org/');
    }

    public function testMutatingSchemeStripsOffDelimiter()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withScheme('https://');
        $this->assertEquals('https', $new->getScheme());
    }

    public function testESchemeStripsOffDelimiter()
    {
        $uri = new Uri('https://example.com');
        $new = $uri->withScheme('://');
        $this->assertEquals('', $new->getScheme());
    }

    public function invalidSchemes()
    {
        return [
            'mailto' => [ 'mailto' ],
            'ftp'    => [ 'ftp' ],
            'telnet' => [ 'telnet' ],
            'ssh'    => [ 'ssh' ],
            'git'    => [ 'git' ],
        ];
    }

    /**
     * @dataProvider invalidSchemes
     */
    public function testConstructWithUnsupportedSchemeRaisesAnException($scheme)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Unsupported scheme');
        $uri = new Uri($scheme . '://example.com');
    }

    /**
     * @dataProvider invalidSchemes
     */
    public function testMutatingWithUnsupportedSchemeRaisesAnException($scheme)
    {
        $uri = new Uri('http://example.com');
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Unsupported scheme');
        $uri->withScheme($scheme);
    }

    public function testPathIsNotPrefixedWithSlashIfSetWithoutOne()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPath('foo/bar');
        $this->assertEquals('foo/bar', $new->getPath());
    }

    public function testPathNotSlashPrefixedIsEmittedWithSlashDelimiterWhenUriIsCastToString()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withPath('foo/bar');
        $this->assertEquals('http://example.com/foo/bar', $new->__toString());
    }

    public function testStripsQueryPrefixIfPresent()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withQuery('?foo=bar');
        $this->assertEquals('foo=bar', $new->getQuery());
    }

    public function testEncodeFragmentPrefixIfPresent()
    {
        $uri = new Uri('http://example.com');
        $new = $uri->withFragment('#/foo/bar');
        $this->assertEquals('%23/foo/bar', $new->getFragment());
    }

    public function standardSchemePortCombinations()
    {
        return [
            'http'  => [ 'http', 80 ],
            'https' => [ 'https', 443 ],
        ];
    }

    /**
     * @dataProvider standardSchemePortCombinations
     */
    public function testAuthorityOmitsPortForStandardSchemePortCombinations($scheme, $port)
    {
        $uri = (new Uri())
            ->withHost('example.com')
            ->withScheme($scheme)
            ->withPort($port);
        $this->assertEquals('example.com', $uri->getAuthority());
    }

    public function mutations()
    {
        return [
            'scheme'    => ['withScheme', 'https'],
            'user-info' => ['withUserInfo', 'foo'],
            'host'      => ['withHost', 'www.example.com'],
            'port'      => ['withPort', 8080],
            'path'      => ['withPath', '/changed'],
            'query'     => ['withQuery', 'changed=value'],
            'fragment'  => ['withFragment', 'changed'],
        ];
    }

    /**
     * @group 48
     * @dataProvider mutations
     */
    public function testMutationResetsUriStringPropertyInClone($method, $value)
    {
        $uri = new Uri('http://example.com/path?query=string#fragment');
        $string = (string) $uri;
        $this->assertAttributeEquals($string, 'uriString', $uri);
        $test = $uri->{$method}($value);
        $this->assertAttributeInternalType('null', 'uriString', $test);
        $this->assertAttributeEquals($string, 'uriString', $uri);
    }

    /**
     * @group 40
     */
    public function testPathIsProperlyEncoded()
    {
        $uri = (new Uri())->withPath('/foo^bar');
        $expected = '/foo%5Ebar';
        $this->assertEquals($expected, $uri->getPath());
    }

    public function testPathDoesNotBecomeDoubleEncoded()
    {
        $uri = (new Uri())->withPath('/foo%5Ebar');
        $expected = '/foo%5Ebar';
        $this->assertEquals($expected, $uri->getPath());
    }

    public function queryStringsForEncoding()
    {
        return [
            'key-only' => ['k^ey', 'k%5Eey'],
            'key-value' => ['k^ey=valu`', 'k%5Eey=valu%60'],
            'array-key-only' => ['key[]', 'key%5B%5D'],
            'array-key-value' => ['key[]=valu`', 'key%5B%5D=valu%60'],
            'complex' => ['k^ey&key[]=valu`&f<>=`bar', 'k%5Eey&key%5B%5D=valu%60&f%3C%3E=%60bar'],
        ];
    }

    /**
     * @group 40
     * @dataProvider queryStringsForEncoding
     */
    public function testQueryIsProperlyEncoded($query, $expected)
    {
        $uri = (new Uri())->withQuery($query);
        $this->assertEquals($expected, $uri->getQuery());
    }

    /**
     * @group 40
     * @dataProvider queryStringsForEncoding
     */
    public function testQueryIsNotDoubleEncoded($query, $expected)
    {
        $uri = (new Uri())->withQuery($expected);
        $this->assertEquals($expected, $uri->getQuery());
    }

    /**
     * @group 40
     */
    public function testFragmentIsProperlyEncoded()
    {
        $uri = (new Uri())->withFragment('/p^th?key^=`bar#b@z');
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        $this->assertEquals($expected, $uri->getFragment());
    }

    /**
     * @group 40
     */
    public function testFragmentIsNotDoubleEncoded()
    {
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        $uri = (new Uri())->withFragment($expected);
        $this->assertEquals($expected, $uri->getFragment());
    }

    public function testProperlyTrimsLeadingSlashesToPreventXSS()
    {
        $url = 'http://example.org//zend.com';
        $uri = new Uri($url);
        $this->assertEquals('http://example.org/zend.com', (string) $uri);
    }

    public function invalidStringComponentValues()
    {
        $methods = [
            'withScheme',
            'withUserInfo',
            'withHost',
            'withPath',
            'withQuery',
            'withFragment',
        ];

        $values = [
            'null'       => null,
            'true'       => true,
            'false'      => false,
            'zero'       => 0,
            'int'        => 1,
            'zero-float' => 0.0,
            'float'      => 1.1,
            'array'      => ['value'],
            'object'     => (object)['value' => 'value'],
        ];

        $combinations = [];
        foreach ($methods as $method) {
            foreach ($values as $type => $value) {
                $key = sprintf('%s-%s', $method, $type);
                $combinations[$key] = [$method, $value];
            }
        }

        return $combinations;
    }

    /**
     * @group 80
     * @dataProvider invalidStringComponentValues
     */
    public function testPassingInvalidValueToWithMethodRaisesException($method, $value)
    {
        $uri = new Uri('https://example.com/');
        $this->expectException('InvalidArgumentException');
        $uri->$method($value);
    }

    public function testUtf8Uri()
    {
        $uri = new Uri('http://ουτοπία.δπθ.gr/');

        $this->assertEquals('ουτοπία.δπθ.gr', $uri->getHost());
    }

    /**
     * @dataProvider utf8PathsDataProvider
     */
    public function testUtf8Path($url, $result)
    {
        $uri = new Uri($url);

        $this->assertEquals($result, $uri->getPath());
    }


    public function utf8PathsDataProvider()
    {
        return [
            ['http://example.com/тестовый_путь/', '/тестовый_путь/'],
            ['http://example.com/ουτοπία/', '/ουτοπία/']
        ];
    }

    /**
     * @dataProvider utf8QueryStringsDataProvider
     */
    public function testUtf8Query($url, $result)
    {
        $uri = new Uri($url);

        $this->assertEquals($result, $uri->getQuery());
    }

    public function utf8QueryStringsDataProvider()
    {
        return [
            ['http://example.com/?q=тестовый_путь', 'q=тестовый_путь'],
            ['http://example.com/?q=ουτοπία', 'q=ουτοπία'],
        ];
    }

    public function testUriDoesNotAppendColonToHostIfPortIsEmpty()
    {
        $uri = (new Uri())->withHost('google.com');
        $this->assertEquals('//google.com', (string) $uri);
    }

    public function testAuthorityIsPrefixedByDoubleSlashIfPresent()
    {
        $uri = (new Uri())->withHost('example.com');
        $this->assertEquals('//example.com', (string) $uri);
    }
}
