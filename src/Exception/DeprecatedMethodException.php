<?php
namespace Phly\Http\Exception;

use BadMethodCallException;

/**
 * Exception indicating a deprecated method.
 */
class DeprecatedMethodException extends BadMethodCallException implements ExceptionInterface
{
}
