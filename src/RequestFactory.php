<?php
namespace Phly\Http;

use Psr\Http\Message\RequestInterface as RequestInterface;
use stdClass;

/**
 * Class for marshaling a request object from the current PHP environment.
 *
 * Logic largely refactors ZF2's Zend\Http\PhpEnvironment\Request class.
 *
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
abstract class RequestFactory
{
    /**
     * Populates a request object from the given $_SERVER array
     *
     * @param array $server
     * @param RequestInterface $request
     * @return RequestInterface The $request provided, only populated with values
     */
    public static function fromServer(array $server, RequestInterface $request = null)
    {
        $server = self::normalizeServer($server);

        if (! $request) {
            $protocol = self::get('SERVER_PROTOCOL', $server, '1.1');
            $request  = new Request();
            $request->setProtocolVersion($protocol);
        }

        $request->setMethod(self::get('REQUEST_METHOD', $server, 'GET'));
        $request->setHeaders(self::marshalHeaders($server));
        $request->setUrl(self::marshalUri($server, $request));
        return $request;
    }

    /**
     * Access a value in an array, returning a default value if not found
     *
     * @param string $key
     * @param array $values
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, array $values, $default = null)
    {
        if (array_key_exists($key, $values)) {
            return $values[$key];
        }
        return $default;
    }

    /**
     * Marshal the $_SERVER array
     *
     * Pre-processes and returns the $_SERVER superglobal.
     *
     * @return array
     */
    public static function normalizeServer(array $server)
    {
        // This seems to be the only way to get the Authorization header on Apache
        if (isset($server['HTTP_AUTHORIZATION'])
            || ! function_exists('apache_request_headers')
        ) {
            return $server;
        }

        $apacheRequestHeaders = apache_request_headers();
        if (isset($apacheRequestHeaders['Authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['Authorization'];
            return $server;
        }

        if (isset($apacheRequestHeaders['authorization'])) {
            $server['HTTP_AUTHORIZATION'] = $apacheRequestHeaders['authorization'];
            return $server;
        }

        return $server;
    }

    /**
     * Marshal headers from $_SERVER
     *
     * @param array $server
     * @return array
     */
    public static function marshalHeaders(array $server)
    {
        $headers = array();
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_COOKIE') === 0) {
                // Cookies are handled using the $_COOKIE superglobal
                continue;
            }

            if ($value && strpos($key, 'HTTP_') === 0) {
                $name = strtr(substr($key, 5), '_', ' ');
                $name = strtr(ucwords(strtolower($name)), ' ', '-');

                $headers[$name] = $value;
                continue;
            }

            if ($value && strpos($key, 'CONTENT_') === 0) {
                $name = substr($key, 8); // Content-
                $name = 'Content-' . (($name == 'MD5') ? $name : ucfirst(strtolower($name)));
                $headers[$name] = $value;
                continue;
            }
        }

        return $headers;
    }

    /**
     * Marshal the URI from the $_SERVER array and headers
     *
     * @param array $server
     * @param RequestInterface $request
     * @return Uri
     */
    public static function marshalUri(array $server, RequestInterface $request)
    {
        // URI scheme
        $scheme = 'http';
        $https = self::get('HTTPS', $server);
        if (($https && 'off' !== $https)
            || $request->getHeader('x-forwarded-proto') == 'https'
        ) {
            $scheme = 'https';
        }

        // Set the host
        $accumulator = (object) ['host' => '', 'port' => null];
        self::marshalHostAndPort($accumulator, $server, $request);
        $host = $accumulator->host;
        $port = $accumulator->port;

        // URI path
        $path = self::marshalRequestUri($server);
        $path = self::stripQueryString($path);

        // URI query
        $query = null;
        if (isset($server['QUERY_STRING'])) {
            $query = ltrim($server['QUERY_STRING'], '?');
        }

        return Uri::fromArray(compact(
            'scheme',
            'host',
            'port',
            'path',
            'query'
        ));
    }

    /**
     * Marshal the host and port from HTTP headers and/or the PHP environment
     *
     * @param array $server
     * @param RequestInterface $request
     * @return array Array with two members, host and port, at indices 0 and 1, respectively
     */
    public static function marshalHostAndPort(stdClass $accumulator, array $server, RequestInterface $request)
    {
        if ($request->hasHeader('host')) {
            return self::marshalHostAndPortFromHeader($accumulator, $request);
        }

        if (! isset($server['SERVER_NAME'])) {
            return;
        }

        $accumulator->host = $server['SERVER_NAME'];
        if (isset($server['SERVER_PORT'])) {
            $accumulator->port = (int) $server['SERVER_PORT'];
        }

        if (! isset($server['SERVER_ADDR']) || ! preg_match('/^\[[0-9a-fA-F\:]+\]$/', $accumulator->host)) {
            return;
        }

        // Misinterpreted IPv6-Address
        // Reported for Safari on Windows
        self::marshalIpv6HostAndPort($accumulator, $server);
    }

    /**
     * Detect the base URI for the request
     *
     * Looks at a variety of criteria in order to attempt to autodetect a base
     * URI, including rewrite URIs, proxy URIs, etc.
     *
     * From ZF2's Zend\Http\PhpEnvironment\Request class
     * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
     * @license   http://framework.zend.com/license/new-bsd New BSD License
     *
     * @param array $server
     * @return string
     */
    public static function marshalRequestUri(array $server)
    {
        // IIS7 with URL Rewrite: make sure we get the unencoded url
        // (double slash problem).
        $iisUrlRewritten = self::get('IIS_WasUrlRewritten', $server);
        $unencodedUrl    = self::get('UNENCODED_URL', $server, '');
        if ('1' == $iisUrlRewritten && ! empty($unencodedUrl)) {
            return $unencodedUrl;
        }

        $requestUri = self::get('REQUEST_URI', $server);

        // Check this first so IIS will catch.
        $httpXRewriteUrl = self::get('HTTP_X_REWRITE_URL', $server);
        if ($httpXRewriteUrl !== null) {
            $requestUri = $httpXRewriteUrl;
        }

        // Check for IIS 7.0 or later with ISAPI_Rewrite
        $httpXOriginalUrl = self::get('HTTP_X_ORIGINAL_URL', $server);
        if ($httpXOriginalUrl !== null) {
            $requestUri = $httpXOriginalUrl;
        }

        if ($requestUri !== null) {
            return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
        }

        $origPathInfo = self::get('ORIG_PATH_INFO', $server);
        if (empty($origPathInfo)) {
            return '/';
        }

        return $origPathInfo;
    }

    /**
     * Strip the query string from a path
     *
     * @param mixed $path
     * @return void
     */
    public static function stripQueryString($path)
    {
        if (($qpos = strpos($path, '?')) !== false) {
            return substr($path, 0, $qpos);
        }
        return $path;
    }

    /**
     * Marshal the host and port from the request header
     *
     * @param stdClass $accumulator
     * @param RequestInterface $request
     * @return void
     */
    private static function marshalHostAndPortFromHeader(stdClass $accumulator, RequestInterface $request)
    {
        $accumulator->host = $request->getHeader('host');
        $accumulator->port = null;

        // works for regname, IPv4 & IPv6
        if (preg_match('|\:(\d+)$|', $accumulator->host, $matches)) {
            $accumulator->host = substr($accumulator->host, 0, -1 * (strlen($matches[1]) + 1));
            $accumulator->port = (int) $matches[1];
        }
    }

    /**
     * Marshal host/port from misinterpreted IPv6 address
     *
     * @param stdClass $accumulator
     * @param array $server
     */
    private static function marshalIpv6HostAndPort(stdClass $accumulator, array $server)
    {
        $accumulator->host = '[' . $server['SERVER_ADDR'] . ']';
        $accumulator->port = $accumulator->port ?: 80;
        if ($accumulator->port . ']' == substr($accumulator->host, strrpos($accumulator->host, ':')+1)) {
            // The last digit of the IPv6-Address has been taken as port
            // Unset the port so the default port can be used
            $accumulator->port = null;
        }
    }
}
