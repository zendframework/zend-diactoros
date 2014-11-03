<?php
namespace Phly\Http;

use Psr\Http\Message\StreamableInterface;

/**
 * Trait implementing the various methods defined in
 * \Psr\Http\Message\MessageInterface.
 */
trait MessageTrait
{
    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $protocol = '1.1';

    /**
     * @var StreamableInterface
     */
    private $stream;

    /**
     * Gets the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamableInterface Returns the body stream.
     */
    public function getBody()
    {
        return $this->stream;
    }

    /**
     * Gets all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     * @return array Returns an associative array of the message's headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     *
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($header)
    {
        return array_key_exists(strtolower($header), $this->headers);
    }

    /**
     * Retrieve a header by the given case-insensitive name as a string.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * @param string $header Case-insensitive header name.
     *
     * @return string
     */
    public function getHeader($header)
    {
        $header = $this->getHeaderAsArray($header);
        if (! $header) {
            return '';
        }

        return implode(',', $header);
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param string $header Case-insensitive header name.
     *
     * @return string[]
     */
    public function getHeaderAsArray($header)
    {
        if (! $this->hasHeader($header)) {
            return [];
        }

        $header = $this->headers[strtolower($header)];
        $header = is_array($header) ? $header : [$header];
        return $header;
    }
}
