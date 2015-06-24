<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * This file exists to allow overriding the various output-related functions
 * in order to test what happens during the `Server::listen()` cycle.
 *
 * These functions include:
 *
 * - headers_sent(): we want to always return false so that headers will be
 *   emitted, and we can test to see their values.
 * - header(): we want to aggregate calls to this function.
 *
 * The HeaderStack class then aggregates that information for us, and the test
 * harness resets the values pre and post test.
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */


namespace ZendTest\Diactoros\TestAsset;

/**
 * A simple class that triggers warning when serialized as JSON
 */
class JsonSerializableObject implements \JsonSerializable
{
    /**
     * @return array
     */
    public function jsonSerialize()
    {
        trigger_error('This error is expected', E_USER_WARNING);
        return [];
    }
}
