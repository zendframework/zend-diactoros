<?php
namespace Phly\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamableInterface;

/**
 * Trait implementing the various methods defined in
 * \Psr\Http\Message\ MessageInterface.
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
     *
     * @return void
     */
    public function setHeader($header, $value)
    {
        if (! is_string($value) && ! is_array($value)) {
            throw new InvalidArgumentException('Invalid header value; must be a string or array of strings');
        }

        if (is_array($value)) {
            $valid = true;
            array_walk($value, function ($value) use (&$valid) {
                if (! is_string($value)) {
                    $valid = false;
                }
            });

            if (! $valid) {
                throw new InvalidArgumentException('Invalid header value; must be a string or array of strings');
            }
        }

        if (is_string($value)) {
            $value = [$value];
        }

        $this->headers[strtolower($header)] = $value;
    }

    /**
     * Sets headers, replacing any headers that have already been set on the message.
     *
     * The array keys MUST be a string. The array values must be either a
     * string or an array of strings.
     *
     * @param array $headers Headers to set.
     *
     * @return void
     */
    public function setHeaders(array $headers)
    {
        $this->headers = [];

        foreach ($headers as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('One or more keys in the headers array is not a string');
            }

            $this->setHeader($key, $value);
        }
    }

    /**
     * Appends a header value for the specified header.
     *
     * Existing values for the specified header will be maintained. The new
     * value will be appended to the existing list.
     *
     * @param string $header Header name to add
     * @param string $value  Value of the header
     *
     * @return void
     */
    public function addHeader($header, $value)
    {
        $header = strtolower($header);

        if (! is_string($value)) {
            throw new InvalidArgumentException('Invalid header value; must be a string');
        }

        if (! $this->hasHeader($header)) {
            $this->setHeader($header, $value);
            return;
        }

        $this->headers[$header][] = $value;
    }

    /**
     * Merges in an associative array of headers.
     *
     * Each array key MUST be a string representing the case-insensitive name
     * of a header. Each value MUST be either a string or an array of strings.
     * For each value, the value is appended to any existing header of the same
     * name, or, if a header does not already exist by the given name, then the
     * header is added.
     *
     * @param array $headers Associative array of headers to add to the message
     *
     * @return void
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $header => $value) {
            $this->addHeader($header, $value);
        }
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
}
