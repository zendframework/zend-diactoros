<?php
namespace Phly\Http;

use InvalidArgumentException;

/**
 * URI implementation
 *
 * @property-read string $scheme
 * @property-read string $host
 * @property-read int $port
 * @property-read string $path
 * @property-read string $query
 * @property-read string $fragment
 */
class Uri
{
    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $query;

    /**
     * @var string
     */
    private $fragment;

    /**
     * Combination of host + port (if non-standard schema + port pairing)
     *
     * @var string
     */
    private $domain;

    /**
     * @var string
     */
    private $uri;

    /**
     * Create a URI based on the parts provided.
     *
     * Parts SHOULD contain the following:
     *
     * - scheme (defaults to http)
     * - host
     * - port (defaults to null)
     * - path
     * - query
     * - fragment
     *
     * All but scheme and host are optional.
     *
     * @param array $parts
     * @return self
     */
    public static function fromArray(array $parts)
    {
        $scheme   = isset($parts['scheme'])   ? $parts['scheme']   : 'http';
        $host     = isset($parts['host'])     ? $parts['host']     : '';
        $port     = isset($parts['port'])     ? $parts['port']     : null;
        $path     = isset($parts['path'])     ? $parts['path']     : '';
        $query    = isset($parts['query'])    ? $parts['query']    : '';
        $fragment = isset($parts['fragment']) ? $parts['fragment'] : '';

        return new static(self::createUriString(
            $scheme,
            $host,
            $port,
            $path,
            $query,
            $fragment
        ));
    }

    /**
     * @param string $uri
     */
    public function __construct($uri)
    {
        $this->uri = $uri;
        if ($this->isValid()) {
            $this->parseUri();
        }
    }

    /**
     * Retrieve properties
     *
     * @param string $name
     * @return null|string|int
     */
    public function __get($name)
    {
        if (! property_exists($this, $name)) {
            return null;
        }

        return $this->{$name};
    }

    /**
     * Return string representation of URI
     *
     * @return string
     */
    public function __toString()
    {
        return $this->uri;
    }

    /**
     * Is the URI valid?
     *
     * Not using filter_var + FILTER_VALIDATE_URL because perfectly valid
     * URIs were being flagged as invalid (e.g., https://local.example.com:3001/foo).
     *
     * @return bool
     */
    public function isValid()
    {
        $parts = parse_url($this->uri);
        if (empty($parts['scheme'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        if ($scheme !== 'file' && empty($parts['host'])) {
            return false;
        }

        if (in_array($scheme, ['http', 'https']) && empty($parts['path'])) {
            return false;
        }

        return true;
    }

    /**
     * Set a new path in the URI
     *
     * Returns a cloned version of the URI instance, with the new path.
     *
     * If the path is not valid, raises an exception.
     *
     * @param  string $path
     * @return Uri
     * @throws InvalidArgumentException.php
     */
    public function setPath($path)
    {
        if (! $this->isValid()) {
            throw new InvalidArgumentException('Cannot set path on invalid URI');
        }

        $path = $path ?: '/';

        $new       = clone $this;
        $new->path = self::normalizePath($path);
        $new->uri  = self::createUriString(
            $new->scheme,
            $new->host,
            $new->port,
            $path,
            $new->query,
            $new->fragment
        );

        if (! $new->isValid()) {
            throw new InvalidArgumentException('Invalid path provided');
        }
        return $new;
    }

    /**
     * Parse a URI into its parts, and set the properties
     */
    private function parseUri()
    {
        $parts = parse_url($this->uri);

        $this->scheme   = $parts['scheme'];
        $this->host     = $parts['host'];
        $this->port     = isset($parts['port'])     ? $parts['port']     : null;
        $this->path     = $parts['path'];
        $this->query    = isset($parts['query'])    ? $parts['query']    : '';
        $this->fragment = isset($parts['fragment']) ? $parts['fragment'] : '';

        $this->domain = self::isNonStandardPort($this->scheme, $this->host, $this->port)
            ? sprintf('%s:%d', $this->host, $this->port)
            : $this->host;
    }

    /**
     * Create a URI string from its various parts
     *
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @return string
     */
    private static function createUriString($scheme, $host, $port, $path, $query, $fragment)
    {
        $uri = sprintf('%s://%s', $scheme, $host);
        if (self::isNonStandardPort($scheme, $host, $port)) {
            $uri .= sprintf(':%d', $port);
        }

        if ($path) {
            $uri .= self::normalizePath($path);
        }

        if ($query) {
            $uri .= sprintf('?%s', $query);
        }

        if ($fragment) {
            $uri .= sprintf('#%s', $fragment);
        }

        return $uri;
    }

    /**
     * Is a given port non-standard for the current scheme?
     *
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @return bool
     */
    private static function isNonStandardPort($scheme, $host, $port)
    {
        if (! $host || ! $port) {
            return false;
        }

        if ($scheme === 'https' && $port !== 443) {
            return true;
        }

        if ($scheme === 'http' && $port !== 80) {
            return true;
        }

        return false;
    }

    /**
     * Normalize a path by prefixing it with a slash if necessary
     *
     * @param string $path
     * @return string
     */
    private static function normalizePath($path)
    {
        if ('/' === $path[0]) {
            return $path;
        }
        return '/' . $path;
    }
}
