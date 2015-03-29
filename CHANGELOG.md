# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release..

## 0.11.1 - 2015-03-29

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#39](https://github.com/phly/http/pull/39) Always attempt to return a Host
  header from a request if a URI is composed containing a host. This will be
  true for client-side requests only; server-side will only represent what was
  present in the incoming request.
- [#40](https://github.com/phly/http/pull/40) Ensure that path, query string,
  and fragment are each properly encoded, but also prevent against double
  encoding of values.
- [#48](https://github.com/phly/http/pull/48) Cache generated URI string after
  first generation.
- [#42](https://github.com/phly/http/pull/42) Updated `Stream::getSize()` to
  return the stream size if a resource is composed.
- [#41](https://github.com/phly/http/pull/41) Fixed examples in README to
  reflect updates in API.
- [#44](https://github.com/phly/http/pull/44) Retain case sensitivity for
  header names (when returned via `getHeaders()`.
- [#45](https://github.com/phly/http/pull/45) Fix port numeric comparison to
  properly accept port integers specified via strings.
- [#46](https://github.com/phly/http/pull/46)  and
  [#47](https://github.com/phly/http/pull/47) Fix install instructions to ensure
  dependencies are resolved properly.

## 0.11.0 - 2015-02-17

This release contains one backwards incompatible change. The upstream
psr/http-message's `ServerRequestInterface` renamed the following methods:

- `getBodyParams()` was renamed to `getParsedBody()`.
- `withBodyParams()` was renamed to `withParsedBody()`.

Additionally, `withParsedBody()` removes the `array` typehint.

### Added

- `Phly\Http\ServerRequest::getParsedBody()` (replaces `getBodyParams()`)
- `Phly\Http\ServerRequest::withParsedBody()` (replaces `getBodyParams()`)

### Deprecated

- Nothing.

### Removed

- `Phly\Http\ServerRequest::getBodyParams()` (replaced with `getParsedBody()`)
- `Phly\Http\ServerRequest::withBodyParams()` (replaced with `getParsedBody()`)

### Fixed

- `Phly\Http\ServerRequestFactory` was updated to call `withParsedBody()` when
  seeding parsed body data.
- [#34](https://github.com/phly/http/pull/34) Fix `Phly\Http\Response` docblocks
  to mirror those of psr/http-message.
- [#35](https://github.com/phly/http/pull/35) Remove unused variable from test
  bootstrap.
- [#37](https://github.com/phly/http/pull/37) Rename `phpunit.xml` to
  `phpunit.xml.dist`.

## 0.10.2 - 2015-02-11

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#31](https://github.com/phly/http/pull/31) corrects the unit test suite to
  read "Http", and not "Conduit".

## 0.10.1 - 2015-01-28

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- A patch was added to ensure the request-target is always recalculated if not
  set explicitly.

## 0.10.0 - 2015-01-28

This release is backwards incompatible with 0.9.X. It updates its
psr/http-message dependency to 0.7.0, which introduces the following changes:

- `Psr\Http\Message\UriTargetInterface` was renamed to
  `Psr\Http\Message\UriInterface`; `Phly\Http\Uri` was updated to reflect this
  change, as was `Phly\Http\Request`.
- `Psr\Http\Message\UriInterface` removes the methods `isOrigin()`,
  `isAbsolute()`, `isAuthority()`, and `isAsterisk()`, and `Phly\Http\Uri` does
  likewise.
- `Psr\Http\Message\RequestInterface` adds the methods `getRequestTarget()` and
  `withRequestTarget($requestTarget)`; `Phly\Http\Request` was updated to add these.

Unless you were using the methods removed from `Phly\Http\Uri`, this update
should pose no challenges to upgrading.

### Added

- `Phly\Http\Request::getRequestTarget()`, which retrieves the request-target. By
  default, it uses the URI composed in the request to provide a request-target
  in origin-form (and the string "/" if no URI is present or the URI has no
  path).
- `Phly\Http\Request::withRequestTarget($requestTarget)`, which creates a new
  instance with the specified request-target. If the request-target contains any
  whitespace, the method raises an exception.

### Deprecated

- Nothing.

### Removed

- `Phly\Http\Uri::isOrigin()`
- `Phly\Http\Uri::isAbsolute()`
- `Phly\Http\Uri::isAuthority()`
- `Phly\Http\Uri::isAsterisk()`

### Fixed

- Nothing.

## 0.9.1 - 2015-01-27

### Added

- Now tests against HHVM (and passes!).
- Many more tests, particularly against `Stream`, `ServerRequestFactory`, and
  `Uri`.

### Deprecated

- Nothing.

### Removed

- `ServerRequestFactory::fromServer()`; this should have been removed in 0.9.0,
  and was only raising an exception anyways.

### Fixed

- `Stream::attach()` was not assigning the resource to the instance; it now
  does.
- `Uri::__construct()` was creating an error condition instead of an exception
  when a non-string URI was provided.

## 0.9.0 - 2015-01-26

This release breaks compatibility with regards to the "final handler" callable
used with `Server::listen()`. It was previously documented that such a callable
should have the signature:

```php
function ($err = null) {
}
```

[phly/conduit](https://github.com/phly/conduit) was the primary consumer of
this, and used it as a `$next` argument. However, the semantics of `$next`
changed in 0.11.0 to always require a request and response, and moves the
`$error` argument to an optional third argument:

```php
function ($request, $response, $err = null) {
}
```

This release adds a test to validate the behavior, which wasn't previously
tested, and updates the documentation to reflect the above.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- `Server::listen()`'s optional `$finalHandler` argument now has an updated
  signature of `function ($req, $res, $err = null)`.

## 0.8.4 - 2015-01-26

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#23](https://github.com/phly/http/pull/24) and [#24](https://github.com/phly/http/pull/24)
  detailed several use cases where empty paths and root paths were not being
  represented correctly. The correct, normalized form of an origin-form or
  absolute-form request-target is that an empty path should be represented by a
  "/". As of this release, this is now correct.

## 0.8.3 - 2015-01-21

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated `Phly\Http\Uri` to allow empty paths (previously, it had prefixed even
  empty paths with a "/").

## 0.8.2 - 2015-01-19

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated `Phly\Http\Uri` to work under PHP 5.4. (Failed previously due to calling `empty()` on method calls.)

## 0.8.1 - 2015-01-18

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated README.md to reference `psr/http-message:~0.6.0`, as that's the
  minimum version with which the 0.8 series will work.


## 0.8.0 - 2015-01-18

This version is for most intents and purposes a complete rewrite. It reverts in
many respects to the implementations in the 0.6 series:

- `Phly\Http\Request`
- `Phly\Http\Response`
- `Phly\Http\ServerRequest` (which was `IncomingRequest` in 0.6)

However, the messages are now immutable, and setters have been renamed to use
`with` and `without` verbiage to better imply that they now return a new
instance, and do not change state on the given instance on which the method was
called. Constructors have been rewritten to accept the most common arguments
first, and to accept all message properties. (The exception to this is that
`Phly\Http\ServerRequest` only accepts the `$_SERVER` and `$_FILES`
superglobals; all others must be injected via the mutators.)

Additionally, this release updates `Phly\Http\Uri` to follow the
psr/http-message >= 0.6.0 `Psr\Http\Message\UriTargetInterface` definition,
which breaks backwards compatibility. The new implementation remains immutable,
but now defines methods for all URI segments.

### Added

- `Phly\Http\Request`.
- `Phly\Http\Response`.
- `Phly\Http\ServerRequest`, which replaces `Phly\Http\IncomingRequest`.
- `Phly\Http\ServerRequestFactory`, which replaces
  `Phly\Http\IncomingRequestFactory`.

### Deprecated

- Nothing.

### Removed

- `Phly\Http\IncomingRequest` (replaced by `Phly\Http\ServerRequest`).
- `Phly\Http\OutgoingRequest`.
- `Phly\Http\IncomingResponse`.
- `Phly\Http\OutgoingResponse`.
- `Phly\Http\IncomingRequestFactory` (replaced by
  `Phly\Http\ServerRequestFactory`).

### Fixed

- Nothing.


## 0.7.2 - 2015-01-18

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#20](https://github.com/phly/http/pull/20) Fixes `IncomingRequest::setUrl()` to ensure it uses the `Uri` instance when setting the property, and not the string URL.
- [#17](https://github.com/phly/http/pull/17) Fixes a typo in the README, and demonstrates instantiating an `OutgoingResponse` prior to consuming it.


## 0.7.1 - 2014-11-05

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- README.md file now reflects actual API and presents client-side use cases.


## 0.7.0 - 2014-11-04

Updated component to psr/http-message 0.5.1. That release contains a number of backwards-incompatible changes:

- `Phly\Http\Request` and `Phly\Http\Response` were removed.
- `Phly\Http\MessageTrait` was altered to provide only accessors.
- `Phly\Http\IncomingResponse`, `Phly\Http\OutgoingResponse`, and `Phly\Http\OutgoingRequest` were added, providing segregation between client-side and server-side request/response pairs, and marking "incoming" variants as immutable. This necessitated adding `Phly\Http\RequestTrait`, `Phly\Http\ResponseTrait`, `Phly\Http\WritableMessageTrait`, and `Phly\Http\ImmutableHeadersTrait` to reduce code duplication and codify commonalities between different types.

### Added

- `Phly\Http\WritableMessageTrait` was added, and contains mutator (setter) methods for the properties defined in `Phly\Http\MessageTrait`. `setBody()` no longer allows setting a `null` value.
- `Phly\Http\ImmutableHeadersTrait` was added, and contains a _private_ mutator (setter) method for populating the headers of a message; this will typically be composed into an immutable message type to allow setting headers via the constructor in a normalized fashion.
- `Phly\Http\RequestTrait` was added, defining accessors (getters) for request instances: the request method and url.
- `Phly\Http\ResponseTrait` was added, defining accessors (getters) for response instances: the status code and reason phrase.
- `Phly\Http\IncomingRequest::__construct()` was modified to allow providing the url, request method, headers, and `$_SERVER` values. Please note the change in constructor arguments when populating an incoming request instance manually.
- `Phly\Http\IncomingRequest::getAttribute($attribute, $default = null)` was added to allow easy retrieval of a single attribute value.
- `Phly\Http\IncomingRequest::setAttribute($attribute, $value)` was added to allow easy setting of a single attribute value.
- `Phly\Http\OutgoingResponse` was added, as the complement to `Phly\Http\IncomingRequest`. It is fully mutable.
- `Phly\Http\OutgoingRequest` was added, for making client-side HTTP requests. It is fully mutable.
- `Phly\Http\IncomingResponse` was added, as a complement to `Phly\Http\OutgoingRequest`, and represents the response returned from such a request. It is immutable, and all values must be set via the constructor.

### Deprecated

- `Phly\Http\OutgoingRequest::setUrl()` no longer allows passing a `Phly\Http\Uri` (or an other object) instance; only strings are allowed.
- `Phly\Http\RequestTrait::getUrl()` no longer returns a `Phly\Http\Uri` instance, only strings.
- `Phly\Http\IncomingRequestFactory::fromServer()`; since all values of a request must be added during instantiation, this method no longer made sense for marshaling values for an existing request instance. This method now throws
- `Phly\Http\IncomingRequestFactory::marshalUri()`; we can no longer assume we have a request instance, only headers. As such, `Phly\Http\IncomingRequestFactory::marshalUriFromServer()` is now preferred as it uses the header values instead (`marshalUri()` now proxies to this method).
- `Phly\Http\IncomingRequestFactory::marshalHostAndPort()`; we can no longer assume we have a request instance, only headers. As such, `Phly\Http\IncomingRequestFactory::marshalHostAndPortFromHeaders()` is now preferred as it uses the header values instead (`marshalHostAndPort()` now proxies to this method).

### Removed

- All mutator methods (setters) were removed from `Phly\Http\MessageTrait`.
- `Phly\Http\Request` was removed.
- `Phly\Http\Response` was removed.
- `Phly\Http\IncomingRequest::setCookieParams()` was removed.
- `Phly\Http\IncomingRequest::setBodyParams()` was removed.

### Fixed

- CS issues.
- Removed obsolete tests.

## 0.6.0 - 2014-10-17

Updated component to psr/http-message 0.4.0. That release contains a number of backwards-incompatible changes.

### Added

- Added IncomingRequestFactory::setHeaders() for simplifying setting
  (overwriting) many headers at once from an array.
- Updated MessageTrait::addHeader() to allow array values
- Modified IncomingRequest to `s/PathParams/Attributes/g`

### Deprecated

- IncomingRequest now only allows arrays for either input or return values; Array-like objects are no longer accepted.
- Removed ability to pass objects to MessageTrait::addHeader()/setHeader()
- Removed setHeaders()/addHeaders() from MessageTrait
- Modified IncomingRequest to `s/PathParams/Attributes/g`

### Removed

- Removed ability to pass objects to MessageTrait::addHeader()/setHeader()
- Removed setHeaders()/addHeaders() from MessageTrait
- Modified IncomingRequest to `s/PathParams/Attributes/g`

### Fixed

- [#11](https://github.com/phly/http/pull/11) Moved `PhlyTest` autoloader configuration to `autoload-dev` key, as it is needed for development purposes only.

## 0.5.0 - 2014-10-13

This release has some backwards incompatible breaks, including:

- `Phly\Http\Request` no longer accepts an HTTP protocol version as a constructor argument. Use `setProtocolVersion()` instead.
- `Phly\Http\Request` now uses `php://memory` as the default body stream. (`IncomingRequest` uses `php://input` as the default stream.)
- `Phly\Http\RequestFactory` has been renamed to `Phly\Http\IncomingRequestFactory`
  - It also now expects an `IncomingRequestInterface` if passed a request object to populate.
- `Phly\Http\Server::createServer()` now expects 4 additional arguments:
  - `$query`, usually `$_GET`
  - `$body`, usually `$_POST`
  - `$cookies`, usually `$_COOKIE`
  - `$files`, usually `$_FILES`
- `Phly\Http\Server` now composes a `Psr\Http\Message\IncomingRequestInterface` instance, not a `Psr\Http\Message\RequestInterface` instance. This has the implication that all handlers will now receive more specifically an `IncomingRequest`. The change affects each of the following method signatures:
  - `__construct()`
  - `createServer()`
  - `createServerFromRequest()`
  
### Added

- `Phly\Http\MessageTrait::setProtocolVersion($version)`, per changes in PSR-7 (this is now defined in the `MessageInterface`).
- Note in `Phly\Http\Stream::read()`'s `@return` annotation indicating that it can also return boolean `false`.
- `Phly\Http\IncomingRequest`, which implements `Psr\Http\Message\IncomingRequestInterface` and provides a server-side request implementation with accessors for each type of request datum typically accessed (cookies, matched path parameters, query string arguments, body parameters, and upload file information). It uses `php://input` as the default body stream.
- `Phly\Http\IncomingRequestFactory` (which replaces `Phly\Http\RequestFactory`)
  - `fromGlobals($server, $query, $body, $cookies, $files)` factory method for creating an `IncomingRequest` instance from superglobals; all arguments are optional, and, if not provided, will be derived from the relevant superglobal.
- `Phly\Http\Server::createServer()` now expects 4 additional arguments:
  - `$query`, usually `$_GET`
  - `$body`, usually `$_POST`
  - `$cookies`, usually `$_COOKIE`
  - `$files`, usually `$_FILES`

### Deprecated

- `Phly\Http\Request` no longer accepts an HTTP protocol version as a constructor argument. Use `setProtocolVersion()` instead.
- `Phly\Http\Server` no longer works with standard `Psr\Http\Message\RequestInterface` implementations; it requires `Psr\Http\Message\IncomingRequestInterface` implementations.

### Removed

- `Phly\Http\RequestFactory` (it is now `Phly\Http\IncomingRequestFactory`)

### Fixed

- `Phly\Http\Stream::read()` now returns boolean false when the stream is not readable, or no resource is present.


## 0.4.2 - 2014-10-09

### Added

- Ability for header values to allow objects that can be cast to strings. This allows for header objects representing complex values to generate the value.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.4.1 - 2014-10-01

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#8](https://github.com/phly/http/pull/8) Update README.md to remove references to methods and interfaces that have been removed.
- Updated README.md to reference `~0.2.0@dev` as the psr/http-message version when installing via Composer.

## 0.4.0 - 2014-10-01

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated implementation to psr/http-message 0.2.0, which:
  - Renames StreamInterface to StreamableInterface
  - Adds attach() and getMetadata($key = null) to the stream interface
  - Removes the $maxLength argument from the getContents() method of the stream interface

## 0.3.3 - 2014-10-01

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Pinned to 0.1.0 of psr/http-message; v0.2.0 introduces breaking changes, which will require
  updates to this library before we can consume them.

## 0.3.2 - 2014-10-01

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#5](https://github.com/phly/http/pull/5) `Phly\Http\Server::sendHeaders` now _always_ sends
  multiple header lines if a header has multiple values.


## 0.3.1 - 2014-09-03

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- `Phly\Http\Uri` now imports `InvalidArgumentException`.

## 0.3.0 - 2014-08-30

Adds a `php://input`-specific stream implementation to ensure it's always regarded as read-only, and to implement caching.

### Added

- `Phly\Http\PhpInputStream`

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- `Phly\Http\Request` now creates a `Phly\Http\PhpInputStream` by default.


## 0.2.0 - 2014-08-30

Reworks the implementation to strictly follow the PSR HTTP message interfaces. This includes:

- Removing property overloading from the request implementation.
- Removing `Phly\Http\ResponseInterface`, and the related method implementations in the concrete response implementation.

The removed features can be added via decoration or implementing additional interfaces in individual projects using the implementations.

### Added

- `Phly\Http\MessageTrait` (implements `Psr\Http\Message\MessageInterface`).

### Deprecated

- `Phly\Http\ResponseInterface`

### Removed

- `Phly\Http\AbstractMessage` (use `Phly\Http\MessageTrait` now).
- `Phly\Http\ResponseInterface`.
- Methods in `Phly\Http\Response` that implemented `Phly\Http\ResponseInterface`.
- Property overloading in `Phly\Http\RequestInterface`.

### Fixed

- Nothing.

## 0.1.1 - 2014-08-27

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#1](https://github.com/phly/http/pull/1) fixes an issue where `%` symbols could raise errors and result in no output. This was due to using `printf` to emit output, which was chosen for testing reasons; however, this had the aforementioned side effect. Tests were updated to use PHPUnit's `expectOutputString()` method for testing output, and `Server::send()` was modified to use `echo` instead of `printf()`.

## 0.1.0 - 2014-08-25

Initial release.
