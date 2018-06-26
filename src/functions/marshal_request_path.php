<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

/**
 * Detect the path for the request
 *
 * Looks at a variety of criteria in order to attempt to autodetect the base
 * request path, including rewrite URIs, proxy URIs, etc.
 *
 * From ZF2's Zend\Http\PhpEnvironment\Request class
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 *
 * @param array $server SAPI environment array (typically `$_SERVER`)
 * @return string Discovered path
 */
function marshalRequestPath(array $server)
{
    // IIS7 with URL Rewrite: make sure we get the unencoded url
    // (double slash problem).
    $iisUrlRewritten = array_key_exists('IIS_WasUrlRewritten', $server) ? $server['IIS_WasUrlRewritten'] : null;
    $unencodedUrl    = array_key_exists('UNENCODED_URL', $server) ? $server['UNENCODED_URL'] : '';
    if ('1' === $iisUrlRewritten && ! empty($unencodedUrl)) {
        return $unencodedUrl;
    }

    $requestUri = array_key_exists('REQUEST_URI', $server) ? $server['REQUEST_URI'] : null;

    // Check this first so IIS will catch.
    $httpXRewriteUrl = array_key_exists('HTTP_X_REWRITE_URL', $server) ? $server['HTTP_X_REWRITE_URL'] : null;
    if ($httpXRewriteUrl !== null) {
        $requestUri = $httpXRewriteUrl;
    }

    // Check for IIS 7.0 or later with ISAPI_Rewrite
    $httpXOriginalUrl = array_key_exists('HTTP_X_ORIGINAL_URL', $server) ? $server['HTTP_X_ORIGINAL_URL'] : null;
    if ($httpXOriginalUrl !== null) {
        $requestUri = $httpXOriginalUrl;
    }

    if ($requestUri !== null) {
        return preg_replace('#^[^/:]+://[^/]+#', '', $requestUri);
    }

    $origPathInfo = array_key_exists('ORIG_PATH_INFO', $server) ? $server['ORIG_PATH_INFO'] : null;
    if (empty($origPathInfo)) {
        return '/';
    }

    return $origPathInfo;
}
