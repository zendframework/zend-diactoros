<?php
namespace PhlyTest\Http;

use Phly\Http\IncomingRequest;
use Phly\Http\IncomingRequestFactory;
use PHPUnit_Framework_TestCase as TestCase;

class IncomingRequestFactoryTest extends TestCase
{
    public function testGetWillReturnValueIfPresentInArray()
    {
        $array = [
            'foo' => 'bar',
            'bar' => '',
            'baz' => null,
        ];

        foreach ($array as $key => $value) {
            $this->assertSame($value, IncomingRequestFactory::get($key, $array));
        }
    }

    public function testGetWillReturnDefaultValueIfKeyIsNotInArray()
    {
        $try   = [ 'foo', 'bar', 'baz' ];
        $array = [
            'quz'  => true,
            'quuz' => true,
        ];
        $default = 'BAT';

        foreach ($try as $key) {
            $this->assertSame($default, IncomingRequestFactory::get($key, $array, $default));
        }
    }

    public function testReturnsServerValueUnchangedIfHttpAuthorizationHeaderIsPresent()
    {
        $server = [
            'HTTP_AUTHORIZATION' => 'token',
            'HTTP_X_Foo' => 'bar',
        ];
        $this->assertSame($server, IncomingRequestFactory::normalizeServer($server));
    }

    public function testMarshalsExpectedHeadersFromServerArray()
    {
        $server = [
            'HTTP_COOKIE' => 'COOKIE',
            'HTTP_AUTHORIZATION' => 'token',
            'HTTP_CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_FOO_BAR' => 'FOOBAR',
            'CONTENT_MD5' => 'CONTENT-MD5',
            'CONTENT_LENGTH' => 'UNSPECIFIED',
        ];

        $expected = [
            'Authorization' => 'token',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Foo-Bar' => 'FOOBAR',
            'Content-MD5' => 'CONTENT-MD5',
            'Content-Length' => 'UNSPECIFIED',
        ];

        $this->assertEquals($expected, IncomingRequestFactory::marshalHeaders($server));
    }

    public function testStripQueryStringReturnsUnchangedStringIfNoQueryStringDetected()
    {
        $path = '/foo/bar';
        $this->assertSame($path, IncomingRequestFactory::stripQueryString($path));
    }

    public function testStripQueryStringReturnsNormalizedPathWhenQueryStringDetected()
    {
        $path = '/foo/bar?foo=bar';
        $this->assertSame('/foo/bar', IncomingRequestFactory::stripQueryString($path));
    }

    public function testMarshalRequestUriUsesIISUnencodedUrlValueIfPresentAndUrlWasRewritten()
    {
        $server = [
            'IIS_WasUrlRewritten' => '1',
            'UNENCODED_URL' => '/foo/bar',
        ];

        $this->assertEquals($server['UNENCODED_URL'], IncomingRequestFactory::marshalRequestUri($server));
    }

    public function testMarshalRequestUriUsesHTTPXRewriteUrlIfPresent()
    {
        $server = [
            'IIS_WasUrlRewritten' => null,
            'UNENCODED_URL' => '/foo/bar',
            'REQUEST_URI' => '/overridden',
            'HTTP_X_REWRITE_URL' => '/bar/baz',
        ];

        $this->assertEquals($server['HTTP_X_REWRITE_URL'], IncomingRequestFactory::marshalRequestUri($server));
    }

    public function testMarshalRequestUriUsesHTTPXOriginalUrlIfPresent()
    {
        $server = [
            'IIS_WasUrlRewritten' => null,
            'UNENCODED_URL' => '/foo/bar',
            'REQUEST_URI' => '/overridden',
            'HTTP_X_REWRITE_URL' => '/bar/baz',
            'HTTP_X_ORIGINAL_URL' => '/baz/bat',
        ];

        $this->assertEquals($server['HTTP_X_ORIGINAL_URL'], IncomingRequestFactory::marshalRequestUri($server));
    }

    public function testMarshalRequestUriStripsSchemeHostAndPortInformationWhenPresent()
    {
        $server = [
            'REQUEST_URI' => 'http://example.com:8000/foo/bar',
        ];

        $this->assertEquals('/foo/bar', IncomingRequestFactory::marshalRequestUri($server));
    }

    public function testMarshalRequestUriUsesOrigPathInfoIfPresent()
    {
        $server = [
            'ORIG_PATH_INFO' => '/foo/bar',
        ];

        $this->assertEquals('/foo/bar', IncomingRequestFactory::marshalRequestUri($server));
    }

    public function testMarshalRequestUriFallsBackToRoot()
    {
        $server = [];

        $this->assertEquals('/', IncomingRequestFactory::marshalRequestUri($server));
    }

    public function testMarshalHostAndPortUsesHostHeaderWhenPresent()
    {
        $request = new IncomingRequest();
        $request->addHeader('Host', 'example.com');

        $accumulator = (object) ['host' => '', 'port' => null];
        IncomingRequestFactory::marshalHostAndPort($accumulator, [], $request);
        $this->assertEquals('example.com', $accumulator->host);
        $this->assertNull($accumulator->port);
    }

    public function testMarshalHostAndPortWillDetectPortInHostHeaderWhenPresent()
    {
        $request = new IncomingRequest();
        $request->addHeader('Host', 'example.com:8000');

        $accumulator = (object) ['host' => '', 'port' => null];
        IncomingRequestFactory::marshalHostAndPort($accumulator, [], $request);
        $this->assertEquals('example.com', $accumulator->host);
        $this->assertEquals(8000, $accumulator->port);
    }

    public function testMarshalHostAndPortReturnsEmptyValuesIfNoHostHeaderAndNoServerName()
    {
        $request = new IncomingRequest();
        $accumulator = (object) ['host' => '', 'port' => null];
        IncomingRequestFactory::marshalHostAndPort($accumulator, [], $request);
        $this->assertEquals('', $accumulator->host);
        $this->assertNull($accumulator->port);
    }

    public function testMarshalHostAndPortReturnsServerNameForHostWhenPresent()
    {
        $request = new IncomingRequest();
        $server  = [
            'SERVER_NAME' => 'example.com',
        ];
        $accumulator = (object) ['host' => '', 'port' => null];
        IncomingRequestFactory::marshalHostAndPort($accumulator, $server, $request);
        $this->assertEquals('example.com', $accumulator->host);
        $this->assertNull($accumulator->port);
    }

    public function testMarshalHostAndPortReturnsServerPortForPortWhenPresentWithServerName()
    {
        $request = new IncomingRequest();
        $server  = [
            'SERVER_NAME' => 'example.com',
            'SERVER_PORT' => 8000,
        ];
        $accumulator = (object) ['host' => '', 'port' => null];
        IncomingRequestFactory::marshalHostAndPort($accumulator, $server, $request);
        $this->assertEquals('example.com', $accumulator->host);
        $this->assertEquals(8000, $accumulator->port);
    }

    public function testMarshalHostAndPortReturnsServerNameForHostIfServerAddrPresentButHostIsNotIpv6Address()
    {
        $request = new IncomingRequest();
        $server  = [
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_NAME' => 'example.com',
        ];
        $accumulator = (object) ['host' => '', 'port' => null];
        IncomingRequestFactory::marshalHostAndPort($accumulator, $server, $request);
        $this->assertEquals('example.com', $accumulator->host);
    }

    public function testMarshalHostAndPortReturnsServerAddrForHostIfPresentAndHostIsIpv6Address()
    {
        $request = new IncomingRequest();
        $server  = [
            'SERVER_ADDR' => 'FE80::0202:B3FF:FE1E:8329',
            'SERVER_NAME' => '[FE80::0202:B3FF:FE1E:8329]',
            'SERVER_PORT' => 8000,
        ];
        $accumulator = (object) ['host' => '', 'port' => null];
        IncomingRequestFactory::marshalHostAndPort($accumulator, $server, $request);
        $this->assertEquals('[FE80::0202:B3FF:FE1E:8329]', $accumulator->host);
        $this->assertEquals(8000, $accumulator->port);
    }

    public function testMarshalHostAndPortWillDetectPortInIpv6StyleHost()
    {
        $request = new IncomingRequest();
        $server  = [
            'SERVER_ADDR' => 'FE80::0202:B3FF:FE1E:8329',
            'SERVER_NAME' => '[FE80::0202:B3FF:FE1E:8329:80]',
        ];
        $accumulator = (object) ['host' => '', 'port' => null];
        IncomingRequestFactory::marshalHostAndPort($accumulator, $server, $request);
        $this->assertEquals('[FE80::0202:B3FF:FE1E:8329]', $accumulator->host);
        $this->assertEquals(80, $accumulator->port);
    }

    public function testMarshalUriDetectsHttpsSchemeFromServerValue()
    {
        $request = new IncomingRequest();
        $request->addHeader('Host', 'example.com');
        $server  = [
            'HTTPS' => true,
        ];

        $uri = IncomingRequestFactory::marshalUri($server, $request);
        $this->assertInstanceOf('Phly\Http\Uri', $uri);
        $this->assertEquals('https', $uri->scheme);
    }

    public function testMarshalUriUsesHttpSchemeIfHttpsServerValueEqualsOff()
    {
        $request = new IncomingRequest();
        $request->addHeader('Host', 'example.com');
        $server  = [
            'HTTPS' => 'off',
        ];

        $uri = IncomingRequestFactory::marshalUri($server, $request);
        $this->assertInstanceOf('Phly\Http\Uri', $uri);
        $this->assertEquals('http', $uri->scheme);
    }

    public function testMarshalUriDetectsHttpsSchemeFromXForwardedProtoValue()
    {
        $request = new IncomingRequest();
        $request->addHeader('Host', 'example.com');
        $request->addHeader('X-Forwarded-Proto', 'https');
        $server  = [];

        $uri = IncomingRequestFactory::marshalUri($server, $request);
        $this->assertInstanceOf('Phly\Http\Uri', $uri);
        $this->assertEquals('https', $uri->scheme);
    }

    public function testMarshalUriStripsQueryStringFromRequestUri()
    {
        $request = new IncomingRequest();
        $request->addHeader('Host', 'example.com');
        $server = [
            'REQUEST_URI' => '/foo/bar?foo=bar',
        ];

        $uri = IncomingRequestFactory::marshalUri($server, $request);
        $this->assertInstanceOf('Phly\Http\Uri', $uri);
        $this->assertEquals('/foo/bar', $uri->path);
    }

    public function testMarshalUriInjectsQueryStringFromServer()
    {
        $request = new IncomingRequest();
        $request->addHeader('Host', 'example.com');
        $server = [
            'REQUEST_URI' => '/foo/bar?foo=bar',
            'QUERY_STRING' => 'bar=baz',
        ];

        $uri = IncomingRequestFactory::marshalUri($server, $request);
        $this->assertInstanceOf('Phly\Http\Uri', $uri);
        $this->assertEquals('bar=baz', $uri->query);
    }

    public function testStateOfPassedRequestInstanceIsUpdatedWhenPassedToFromServer()
    {
        $request = new IncomingRequest();

        $server = [
            'HTTP_HOST' => 'example.com',
            'HTTP_ACCEPT' => 'application/json',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'bar=baz',
        ];

        $result = IncomingRequestFactory::fromServer($server, $request);
        $this->assertSame($request, $result);
        $this->assertEquals('POST', $request->getMethod());

        $this->assertTrue($request->hasHeader('Accept'));
        $this->assertEquals('application/json', $request->getHeader('Accept'));
        $this->assertTrue($request->hasHeader('Host'));
        $this->assertEquals('example.com', $request->getHeader('Host'));

        $uri = $request->getUrl();
        $this->assertInstanceOf('Phly\Http\Uri', $uri);
        $this->assertEquals('http', $uri->scheme);
        $this->assertEquals('example.com', $uri->host);
        $this->assertNull($uri->port);
        $this->assertEquals('/foo/bar', $uri->path);
        $this->assertEquals('bar=baz', $uri->query);
    }

    public function testFromServerWillCreateARequestInstanceIfNonePassed()
    {
        $server = [
            'HTTP_HOST' => 'example.com',
            'HTTP_ACCEPT' => 'application/json',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'bar=baz',
        ];

        $request = IncomingRequestFactory::fromServer($server);
        $this->assertInstanceOf('Phly\Http\Request', $request);

        $this->assertEquals('1.1', $request->getProtocolVersion());

        $this->assertEquals('POST', $request->getMethod());

        $this->assertTrue($request->hasHeader('Accept'));
        $this->assertEquals('application/json', $request->getHeader('Accept'));
        $this->assertTrue($request->hasHeader('Host'));
        $this->assertEquals('example.com', $request->getHeader('Host'));

        $uri = $request->getUrl();
        $this->assertInstanceOf('Phly\Http\Uri', $uri);
        $this->assertEquals('http', $uri->scheme);
        $this->assertEquals('example.com', $uri->host);
        $this->assertNull($uri->port);
        $this->assertEquals('/foo/bar', $uri->path);
        $this->assertEquals('bar=baz', $uri->query);
    }

    public function testFromServerWillCreateARequestUsingProtocolFromServer()
    {
        $server = [
            'SERVER_PROTOCOL' => '1.0',
            'HTTP_HOST' => 'example.com',
            'HTTP_ACCEPT' => 'application/json',
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/foo/bar',
            'QUERY_STRING' => 'bar=baz',
        ];

        $request = IncomingRequestFactory::fromServer($server);
        $this->assertInstanceOf('Phly\Http\Request', $request);

        $this->assertEquals('1.0', $request->getProtocolVersion());
    }
}
