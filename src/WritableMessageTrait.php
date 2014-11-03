<?php
namespace Phly\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamableInterface;

/**
 * Extends the MessageTrait to add mutators for protocol version, headers, and
 * the content body.
 */
trait WritableMessageTrait
{
    use MessageTrait;

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
     * Sets the body of the message.
     *
     * The body MUST be a StreamableInterface object. Setting the body to null MUST
     * remove the existing body.
     *
     * @param null|StreamableInterface $body Body.
     * @return void
     */
    public function setBody(StreamableInterface $body = null)
    {
        $this->stream = $body;
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
