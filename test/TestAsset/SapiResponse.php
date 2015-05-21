<?php
/**
 * This file exists to allow overriding the various output-related functions
 * in order to test what happens during the `Response\SapiEmitter::emit()` cycle.
 *
 * These functions include:
 *
 * - headers_sent(): we want to always return false so that headers will be
 *   emitted, and we can test to see their values.
 * - header(): we want to aggregate calls to this function.
 *
 * It pushes headers into the HeaderStack class defined in Functions.php.
 */

namespace Phly\Http\Response;

use Phly\Http\HeaderStack;

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
    HeaderStack::push($value);
}
