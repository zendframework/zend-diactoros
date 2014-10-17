<?php
namespace Phly\Http;

use InvalidArgumentException;
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
     * Set the HTTP protocol version
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     * 
     * @param string $version 
     */
    public function setProtocolVersion($version)
    {
        $this->protocol = $version;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamableInterface|null Returns the body, or null if not set.
     */
    public function getBody()
    {
        return $this->stream;
    }

    /**
     * Sets the body of the message.
     *
     * The body MUST be a StreamableInterface object. Setting the body to null MUST
     * remove the existing body.
     *
     * @param StreamableInterface|null $body Body.
     *
     * @return void
     *
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function setBody(StreamableInterface $body = null)
    {
        $this->stream = $body;
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

    /**
     * Sets a header, replacing any existing values of any headers with the
     * same case-insensitive name.
     *
     * The header name is case-insensitive. The header values MUST be a string
     * or an array of strings.
     *
     * @param string $header Header name
     * @param string|string[] $value  Header value(s)
     */
    public function setHeader($header, $value)
    {
        $header = strtolower($header);

        if (is_string($value)) {
            $value = [ $value ];
        }

        if (! is_array($value) || ! $this->arrayContainsOnlyStrings($value)) {
            throw new InvalidArgumentException(
                'Invalid header value; must be a string or array of strings'
            );
        }

        $this->headers[$header] = $value;
    }

    /**
     * Appends a header value for the specified header.
     *
     * Existing values for the specified header will be maintained. The new
     * value will be appended to the existing list.
     *
     * @param string $header Header name to add
     * @param string|string[] $value  Value of the header; a string or array of strings
     */
    public function addHeader($header, $value)
    {
        $header = strtolower($header);

        if (is_string($value)) {
            $value = [ $value ];
        }

        if (! is_array($value) || ! $this->arrayContainsOnlyStrings($value)) {
            throw new InvalidArgumentException(
                'Invalid header value; must be a string or array of strings'
            );
        }

        if (! $this->hasHeader($header)) {
            $this->setHeader($header, $value);
            return;
        }

        $this->headers[$header] = array_merge($this->headers[$header], $value);
    }

    /**
     * Remove a specific header by case-insensitive name.
     *
     * @param string $header HTTP header to remove
     *
     * @return void
     */
    public function removeHeader($header)
    {
        if (! $this->hasHeader($header)) {
            return;
        }

        unset($this->headers[strtolower($header)]);
    }

    /**
     * Test that an array contains only strings
     * 
     * @param array $array 
     * @return bool
     */
    private function arrayContainsOnlyStrings(array $array)
    {
        return array_reduce($array, [ __CLASS__, 'filterStringValue'], true);
    }

    /**
     * Test if a value is a string
     *
     * Used with array_reduce.
     * 
     * @param bool $carry 
     * @param mixed $item 
     * @return bool
     */
    private static function filterStringValue($carry, $item)
    {
        if (! is_string($item)) {
            return false;
        }
        return $carry;
    }
}
