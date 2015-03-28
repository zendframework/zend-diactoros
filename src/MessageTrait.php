<?php
namespace Phly\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamableInterface;

/**
 * Trait implementing the various methods defined in
 * \Psr\Http\Message\MessageInterface.
 *
 * @link https://github.com/php-fig/http-message/tree/master/src/MessageInterface.php
 */
trait MessageTrait
{
    /**
     * List of all registered headers, as key => array of values.
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Map of normalized header name to original name used to register header.
     *
     * @var array
     */
    protected $headerNames = [];

    /**
     * @var string
     */
    private $protocol = '1.1';

    /**
     * @var StreamableInterface
     */
    private $stream;

    /**
     * Retrieves the HTTP protocol version as a string.
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
     * Create a new instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return self
     */
    public function withProtocolVersion($version)
    {
        $new = clone $this;
        $new->protocol = $version;
        return $new;
    }

    /**
     * Retrieves all message headers.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * @return array Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $header Case-insensitive header name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     */
    public function hasHeader($header)
    {
        return array_key_exists(strtolower($header), $this->headerNames);
    }

    /**
     * Retrieve a header by the given case-insensitive name, as a string.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeaderLines() instead
     * and supply your own delimiter when concatenating.
     *
     * @param string $header Case-insensitive header name.
     * @return string|null Null is returned if no value exists.
     */
    public function getHeader($header)
    {
        $value = $this->getHeaderLines($header);
        if (! $value || empty($value)) {
            return null;
        }

        return implode(',', $value);
    }

    /**
     * Retrieves a header by the given case-insensitive name as an array of strings.
     *
     * @param string $header Case-insensitive header name.
     * @return string[]
     */
    public function getHeaderLines($header)
    {
        if (! $this->hasHeader($header)) {
            return [];
        }

        $header = $this->headerNames[strtolower($header)];

        $value = $this->headers[$header];
        $value = is_array($value) ? $value : [$value];
        return $value;
    }

    /**
     * Create a new instance with the provided header, replacing any existing
     * values of any headers with the same case-insensitive name.
     *
     * The header name is case-insensitive. The header values MUST be a string
     * or an array of strings.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new and/or updated header and value.
     *
     * @param string $header Header name
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($header, $value)
    {
        if (is_string($value)) {
            $value = [ $value ];
        }

        if (! is_array($value) || ! $this->arrayContainsOnlyStrings($value)) {
            throw new InvalidArgumentException(
                'Invalid header value; must be a string or array of strings'
            );
        }

        $normalized = strtolower($header);

        $new = clone $this;

        $new->headerNames[$normalized] = $header;
        $new->headers[$header] = $value;
        return $new;
    }

    /**
     * Creates a new instance, with the specified header appended with the
     * given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new header and/or value.
     *
     * @param string $header Header name to add
     * @param string|string[] $value Header value(s).
     * @return self
     * @throws InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($header, $value)
    {
        if (is_string($value)) {
            $value = [ $value ];
        }

        if (! is_array($value) || ! $this->arrayContainsOnlyStrings($value)) {
            throw new InvalidArgumentException(
                'Invalid header value; must be a string or array of strings'
            );
        }

        if (! $this->hasHeader($header)) {
            return $this->withHeader($header, $value);
        }

        $normalized = strtolower($header);
        $header     = $this->headerNames[$normalized];

        $new = clone $this;
        $new->headers[$header] = array_merge($this->headers[$header], $value);
        return $new;
    }

    /**
     * Creates a new instance, without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that removes
     * the named header.
     *
     * @param string $header HTTP header to remove
     * @return self
     */
    public function withoutHeader($header)
    {
        if (! $this->hasHeader($header)) {
            return $this;
        }

        $normalized = strtolower($header);
        $original   = $this->headerNames[$normalized];

        $new = clone $this;
        unset($new->headers[$original], $new->headerNames[$normalized]);
        return $new;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamableInterface Returns the body as a stream.
     */
    public function getBody()
    {
        return $this->stream;
    }

    /**
     * Create a new instance, with the specified message body.
     *
     * The body MUST be a StreamableInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamableInterface $body Body.
     * @return self
     * @throws InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamableInterface $body)
    {
        $new = clone $this;
        $new->stream = $body;
        return $new;
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
     * Filter a set of headers to ensure they are in the correct internal format.
     *
     * Used by message constructors to allow setting all initial headers at once.
     *
     * @param array $originalHeaders Headers to filter.
     * @return array Filtered headers and names.
     */
    private function filterHeaders(array $originalHeaders)
    {
        $headerNames = $headers = [];
        foreach ($originalHeaders as $header => $value) {
            if (! is_string($header)) {
                continue;
            }

            if (! is_array($value) && ! is_string($value)) {
                continue;
            }

            if (! is_array($value)) {
                $value = [ $value ];
            }

            $headerNames[strtolower($header)] = $header;
            $headers[$header] = $value;
        }

        return [ $headerNames, $headers ];
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
