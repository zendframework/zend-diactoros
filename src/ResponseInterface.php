<?php
namespace Phly\Http;

use Psr\Http\Message\ResponseInterface as BaseResponseInterface;

/**
 * Extension to the PSR ResponseInterface
 *
 * Extends the PSR ResponseInterface to provide the following abilities:
 *
 * - Write to the content
 * - End the response (mark it complete)
 * - Determine if the response is complete
 */
interface ResponseInterface extends BaseResponseInterface
{
    /**
     * Write data to the response body
     *
     * Proxies to the underlying stream and writes the provided data to it.
     *
     * @param string $data
     */
    public function write($data);

    /**
     * Mark the response as complete
     *
     * A completed response should no longer allow manipulation of either
     * headers or the content body.
     *
     * If $data is passed, that data should be written to the response body
     * prior to marking the response as complete.
     *
     * @param string $data
     */
    public function end($data = null);

    /**
     * Indicate whether or not the response is complete.
     *
     * I.e., if end() has previously been called.
     *
     * @return bool
     */
    public function isComplete();
}
