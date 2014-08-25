<?php
/**
 * This file exists to allow overriding the various output-related functions
 * in order to test what happens during the `Server::listen()` cycle.
 *
 * These functions include:
 *
 * - headers_sent(): we want to always return false so that headers will be
 *   emitted, and we can test to see their values.
 * - header(): we want to aggregate calls to this function.
 * - printf(): we want to aggregate calls to this function as well; we cannot
 *   do the same with echo as it's a language construct, not a function.
 *
 * The Output class then aggregates that information for us, and the test
 * harness resets the values pre and post test.
 */

namespace Phly\Http;

/**
 * Store output artifacts
 */
abstract class Output
{
    public static $headers = array();
    public static $body;
}

/**
 * Have headers been sent?
 *
 * @return false
 */
function headers_sent()
{
    return false;
}

/**
 * Emit a header, without creating actual output artifacts
 *
 * @param string $value
 */
function header($value)
{
    Output::$headers[] = $value;
}

/**
 * Emit some output, without creating actual output artifacts
 *
 * @param string $template
 */
function printf($template)
{
    Output::$body = $template;
}
