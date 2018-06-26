<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

/**
 * Marshal the host and port from HTTP headers and/or the PHP environment.
 *
 * @param array $headers
 * @param array $server
 * @return array Array of two items, host and port, in that order (can be
 *     passed to a list() operation).
 */
function marshalHostAndPort(array $headers, array $server)
{
    /**
     * @param string|array $host
     * @return array Array of two items, host and port, in that order (can be
     *     passed to a list() operation).
     */
    $marshalHostAndPortFromHeader = function ($host) {
        if (is_array($host)) {
            $host = implode(', ', $host);
        }

        $port = null;

        // works for regname, IPv4 & IPv6
        if (preg_match('|\:(\d+)$|', $host, $matches)) {
            $host = substr($host, 0, -1 * (strlen($matches[1]) + 1));
            $port = (int) $matches[1];
        }

        return [$host, $port];
    };

    /**
     * @param array $server
     * @param string $host
     * @param null|int $port
     * @return array Array of two items, host and port, in that order (can be
     *     passed to a list() operation).
     */
    $marshalIpv6HostAndPort = function (array $server, $host, $port) {
        $host = '[' . $server['SERVER_ADDR'] . ']';
        $port = $port ?: 80;
        if ($port . ']' === substr($host, strrpos($host, ':') + 1)) {
            // The last digit of the IPv6-Address has been taken as port
            // Unset the port so the default port can be used
            $port = null;
        }
        return [$host, $port];
    };

    static $defaults = ['', null];

    if (getHeaderFromArray('host', $headers, false)) {
        return $marshalHostAndPortFromHeader(getHeaderFromArray('host', $headers));
    }

    if (! isset($server['SERVER_NAME'])) {
        return $defaults;
    }

    $host = $server['SERVER_NAME'];
    $port = isset($server['SERVER_PORT']) ? (int) $server['SERVER_PORT'] : null;

    if (! isset($server['SERVER_ADDR'])
        || ! preg_match('/^\[[0-9a-fA-F\:]+\]$/', $host)
    ) {
        return [$host, $port];
    }

    // Misinterpreted IPv6-Address
    // Reported for Safari on Windows
    return $marshalIpv6HostAndPort($server, $host, $port);
}
