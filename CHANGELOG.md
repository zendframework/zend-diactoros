# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release..

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
