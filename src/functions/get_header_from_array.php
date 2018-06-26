<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

/**
 * @param string $name
 * @param array $headers Array of headers
 * @param mixed $default Default value to return if $name not found in $headers.
 * @return mixed
 */
function getHeaderFromArray($name, array $headers, $default = null)
{
    $header  = strtolower($name);
    $headers = array_change_key_case($headers, CASE_LOWER);
    if (array_key_exists($header, $headers)) {
        $value = is_array($headers[$header]) ? implode(', ', $headers[$header]) : $headers[$header];
        return $value;
    }

    return $default;
}
