<?php
namespace Phly\Http;

use InvalidArgumentException;
use Psr\Http\Message\UriTargetInterface;

/**
 * Implementation of Psr\Http\MessageInterface.
 *
 * Provides a value object surrounding a request target, typically a URI.
 *
 * Instances of this class  are considered immutable; all methods that
 * might change state are implemented such that they retain the internal
 * state of the current instance and return a new instance that contains the
 * changed state.
 */
class Uri implements UriTargetInterface
{
    /**
     * @var string
     */
    private $scheme = '';

    /**
     * @var string
     */
    private $userInfo = '';

    /**
     * @var string
     */
    private $host = '';

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $path = '';

    /**
     * @var string
     */
    private $query = '';

    /**
     * @var string
     */
    private $fragment = '';

    /**
     * @param string $uri
     * @throws InvalidArgumentException on non-string $uri argument
     */
    public function __construct($uri = '')
    {
        if (! is_string($uri)) {
            throw new InvalidArgumentException(
                'URI passed to constructor must be a string; received "%s"',
                (is_object($uri) ? get_class($uri) : gettype($uri))
            );
        }

        if (! empty($uri)) {
            $this->parseUri($uri);
        }
    }

    /**
     * Return the string representation of the URI.
     *
     * Concatenates the various segments of the URI, using the appropriate
     * delimiters:
     *
     * - If a scheme is present, "://" MUST append the value.
     * - If the authority information is present, that value will be
     *   contatenated.
     * - If a path is present, it MUST be prefixed by a "/" character.
     * - If a query string is present, it MUST be prefixed by a "?" character.
     * - If a URI fragment is present, it MUST be prefixed by a "#" character.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->isAsterisk()) {
            return '*';
        }

        if ($this->isAuthority()) {
            return $this->getAuthority();
        }

        return self::createUriString(
            $this->scheme,
            $this->getAuthority(),
            $this->path,
            $this->query,
            $this->fragment
        );
    }

    /**
     * Retrieve the URI scheme.
     *
     * Implementations SHOULD restrict values to "http", "https", or an empty
     * string but MAY accommodate other schemes if required.
     *
     * If no scheme is present, this method MUST return an empty string.
     *
     * The string returned MUST omit the trailing "://" delimiter if present.
     *
     * @return string The scheme of the URI.
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority portion of the URI.
     *
     * The authority portion of the URI is:
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * If the port component is not set or is the standard port for the current
     * scheme, it SHOULD NOT be included.
     *
     * This method MUST return an empty string if no authority information is
     * present.
     *
     * @return string Authority portion of the URI, in "[user-info@]host[:port]"
     *     format.
     */
    public function getAuthority()
    {
        if (empty($this->host)) {
            return '';
        }

        $authority = $this->host;
        if (! empty($this->userInfo)) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->isNonStandardPort($this->scheme, $this->host, $this->port)) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * Retrieve the user information portion of the URI, if present.
     *
     * If a user is present in the URI, this will return that value;
     * additionally, if the password is also present, it will be appended to the
     * user value, with a colon (":") separating the values.
     *
     * Implementations MUST NOT return the "@" suffix when returning this value.
     *
     * @return string User information portion of the URI, if present, in
     *     "username[:password]" format.
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * Retrieve the host segment of the URI.
     *
     * This method MUST return a string; if no host segment is present, an
     * empty string MUST be returned.
     *
     * @return string Host segment of the URI.
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the port segment of the URI.
     *
     * If a port is present, and it is non-standard for the current scheme,
     * this method MUST return it as an integer. If the port is the standard port
     * used with the current scheme, this method SHOULD return null.
     *
     * If no port is present, and no scheme is present, this method MUST return
     * a null value.
     *
     * If no port is present, but a scheme is present, this method MAY return
     * the standard port for that scheme, but SHOULD return null.
     *
     * @return null|int The port for the URI.
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Retrieve the path segment of the URI.
     *
     * This method MUST return a string; if no path is present it MUST return
     * an empty string.
     *
     * @return string The path segment of the URI.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     *
     * This method MUST return a string; if no query string is present, it MUST
     * return an empty string.
     *
     * The string returned MUST omit the leading "?" character.
     *
     * @return string The URI query string.
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment segment of the URI.
     *
     * This method MUST return a string; if no fragment is present, it MUST
     * return an empty string.
     *
     * The string returned MUST omit the leading "#" character.
     *
     * @return string The URI fragment.
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Create a new instance with the specified scheme.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified scheme. If the scheme
     * provided includes the "://" delimiter, it MUST be removed.
     *
     * Implementations SHOULD restrict values to "http", "https", or an empty
     * string but MAY accommodate other schemes if required.
     *
     * An empty scheme is equivalent to removing the scheme.
     *
     * @param string $scheme The scheme to use with the new instance.
     * @return self A new instance with the specified scheme.
     * @throws InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme)
    {
        $scheme = strtolower($scheme);
        if (strpos($scheme, '://')) {
            $scheme = str_replace('://', '', $scheme);
        }

        if (! in_array($scheme, ['', 'http', 'https'], true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported scheme "%s"; must be one of an empty string, "http", or "https"',
                $scheme
            ));
        }

        $new = clone $this;
        $new->scheme = $scheme;
        return $new;
    }

    /**
     * Create a new instance with the specified user information.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified user information.
     *
     * Password is optional, but the user information MUST include the
     * user; an empty string for the user is equivalent to removing user
     * information.
     *
     * @param string $user User name to use for authority.
     * @param null|string $password Password associated with $user.
     * @return self A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null)
    {
        $info = $user;
        if ($password) {
            $info .= ':' . $password;
        }

        $new = clone $this;
        $new->userInfo = $info;
        return $new;
    }

    /**
     * Create a new instance with the specified host.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified host.
     *
     * An empty host value is equivalent to removing the host.
     *
     * @param string $host Hostname to use with the new instance.
     * @return self A new instance with the specified host.
     * @throws InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host)
    {
        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    /**
     * Create a new instance with the specified port.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified port.
     *
     * Implementations MUST raise an exception for ports outside the
     * established TCP and UDP port ranges.
     *
     * A null value provided for the port is equivalent to removing the port
     * information.
     *
     * @param null|int $port Port to use with the new instance; a null value
     *     removes the port information.
     * @return self A new instance with the specified port.
     * @throws InvalidArgumentException for invalid ports.
     */
    public function withPort($port)
    {
        if (! is_integer($port) || (is_string($port) && ! is_numeric($port))) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port "%s" specified; must be an integer or integer string',
                (is_object($port) ? get_class($port) : gettype($port))
            ));
        }

        $port = (int) $port;

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port "%d" specified; must be a valid TCP/UDP port',
                $port
            ));
        }

        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    /**
     * Create a new instance with the specified path.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified path.
     *
     * The path MUST be prefixed with "/"; if not, the implementation MAY
     * provide the prefix itself.
     *
     * An empty path value is equivalent to removing the path.
     *
     * @param string $path The path to use with the new instance.
     * @return self A new instance with the specified path.
     * @throws InvalidArgumentException for invalid paths.
     */
    public function withPath($path)
    {
        if (! is_string($path)) {
            throw new InvalidArgumentException(
                'Invalid path provided; must be a string'
            );
        }

        if (strpos($path, '?') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a query string'
            );
        }

        if (strpos($path, '#') !== false) {
            throw new InvalidArgumentException(
                'Invalid path provided; must not contain a URI fragment'
            );
        }

        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    /**
     * Create a new instance with the specified query string.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified query string.
     *
     * If the query string is prefixed by "?", that character MUST be removed.
     * Additionally, the query string SHOULD be parseable by parse_str() in
     * order to be valid.
     *
     * An empty query string value is equivalent to removing the query string.
     *
     * @param string $query The query string to use with the new instance.
     * @return self A new instance with the specified query string.
     * @throws InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query)
    {
        if (! is_string($query)) {
            throw new InvalidArgumentException(
                'Query string must be a string'
            );
        }

        if (strpos($query, '#') !== false) {
            throw new InvalidArgumentException(
                'Query string must not include a URI fragment'
            );
        }

        if (strpos($query, '?') === 0) {
            $query = substr($query, 1);
        }

        if (! $this->validateQuery($query)) {
            throw new InvalidArgumentException(
                'Query string must be parseable by parse_str'
            );
        }

        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    /**
     * Create a new instance with the specified URI fragment.
     *
     * This method MUST retain the state of the current instance, and return
     * a new instance that contains the specified URI fragment.
     *
     * If the fragment is prefixed by "#", that character MUST be removed.
     *
     * An empty fragment value is equivalent to removing the fragment.
     *
     * @param string $fragment The URI fragment to use with the new instance.
     * @return self A new instance with the specified URI fragment.
     */
    public function withFragment($fragment)
    {
        if (strpos($fragment, '#') === 0) {
            $fragment = substr($fragment, 1);
        }

        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    /**
     * Indicate whether the URI is in origin-form.
     *
     * Origin-form is a URI that includes only the path, and optionally the
     * query string.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3.1
     * @return bool
     */
    public function isOrigin()
    {
        return (empty($this->scheme) && empty($this->getAuthority()) && empty($this->fragment));
    }

    /**
     * Indicate whether the URI is absolute.
     *
     * An absolute URI contains minimally a non-empty scheme and non-empty
     * authority.
     *
     * @see getAuthority()
     * @link http://tools.ietf.org/html/rfc7230#section-5.3.2
     * @return bool
     */
    public function isAbsolute()
    {
        return (! empty($this->scheme) && ! empty($this->getAuthority()));
    }

    /**
     * Indicate whether the URI is in authority form.
     *
     * An authority-form URI is an URI that contains ONLY the authority
     * information.
     *
     * @see getAuthority()
     * @link http://tools.ietf.org/html/rfc7230#section-5.3.3
     * @return bool
     */
    public function isAuthority()
    {
        return (
            ! empty($this->getAuthority())
            && empty($this->scheme)
            && empty($this->path)
            && empty($this->query)
            && empty($this->fragment)
        );
    }

    /**
     * Indicate whether the URI is an asterisk-form.
     *
     * An asterisk form URI will contain "*" as the path, and no other URI
     * segments.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3.4
     * @return bool
     */
    public function isAsterisk()
    {
        return (
            empty($this->scheme)
            && empty($this->getAuthority())
            && empty($this->query)
            && empty($this->fragment)
            && $this->path === '*'
        );
    }

    /**
     * Parse a URI into its parts, and set the properties
     */
    private function parseUri($uri)
    {
        $parts = parse_url($uri);

        $this->scheme    = isset($parts['scheme'])   ? $parts['scheme']   : '';
        $this->userInfo  = isset($parts['user'])     ? $parts['user']     : '';
        $this->host      = isset($parts['host'])     ? $parts['host']     : '';
        $this->port      = isset($parts['port'])     ? $parts['port']     : null;
        $this->path      = isset($parts['path'])     ? $parts['path']     : '';
        $this->query     = isset($parts['query'])    ? $parts['query']    : '';
        $this->fragment  = isset($parts['fragment']) ? $parts['fragment'] : '';

        if (isset($parts['pass'])) {
            $this->userInfo .= ':' . $parts['pass'];
        }
    }

    /**
     * Create a URI string from its various parts
     *
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @return string
     */
    private static function createUriString($scheme, $authority, $path, $query, $fragment)
    {
        if ($scheme === 'file') {
            return self::createFileUriString($path);
        }

        return self::createWebUriString($scheme, $authority, $path, $query, $fragment);
    }

    /**
     * Return a URI for a file
     *
     * @param string $path
     * @return string
     */
    private static function createFileUriString($path)
    {
        return sprintf('file://%s', self::normalizePath($path));
    }

    /**
     * Return a URI for a web address
     *
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param string $query
     * @param string $fragment
     * @return string
     */
    private static function createWebUriString($scheme, $authority, $path, $query, $fragment)
    {
        $uri = '';

        if (!empty($scheme)) {
            $uri .= sprintf('%s://', $scheme);
        }

        if (! empty($authority)) {
            $uri .= $authority;
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
        if (! $scheme) {
            return true;
        }

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

    /**
     * Validate a query string
     *
     * @param string $query
     * @return bool
     */
    private function validateQuery($query)
    {
        if (empty($query)) {
            return true;
        }

        $parsed = array();
        parse_str($query, $parsed);
        return (! empty($parsed));
    }
}
