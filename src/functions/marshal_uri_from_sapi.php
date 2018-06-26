<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

/**
 * Marshal a Uri instance based on the values presnt in the $_SERVER array and headers.
 *
 * Internally, uses the logic from the following functions:
 *
 * - getHeaderFromArray(), in order to locate and retrieve header values using
 *   a case-insenstive lookup.
 * - marshalHostAndPort() to locate the host and port values.
 * - marshalRequestPath() to determine the request path from server values.
 *
 * @param array $server SAPI parameters
 * @param array $headers HTTP request headers
 * @return Uri
 */
function marshalUriFromSapi(array $server, array $headers)
{
    $uri = new Uri('');

    // URI scheme
    $scheme = 'http';
    $https  = array_key_exists('HTTPS', $server) ? $server['HTTPS'] : false;
    if (($https && 'off' !== $https)
        || getHeaderFromArray('x-forwarded-proto', $headers, false) === 'https'
    ) {
        $scheme = 'https';
    }
    $uri = $uri->withScheme($scheme);

    // Set the host
    $accumulator = (object) ['host' => '', 'port' => null];
    list($host, $port) = marshalHostAndPort($headers, $server);
    if (! empty($host)) {
        $uri = $uri->withHost($host);
        if (! empty($port)) {
            $uri = $uri->withPort($port);
        }
    }

    // URI path
    $path = marshalRequestPath($server);

    // Strip query string
    $path = false !== ($qpos = strpos($path, '?')) ? substr($path, 0, $qpos) : $path;

    // URI query
    $query = '';
    if (isset($server['QUERY_STRING'])) {
        $query = ltrim($server['QUERY_STRING'], '?');
    }

    // URI fragment
    $fragment = '';
    if (strpos($path, '#') !== false) {
        list($path, $fragment) = explode('#', $path, 2);
    }

    return $uri
        ->withPath($path)
        ->withFragment($fragment)
        ->withQuery($query);
}
