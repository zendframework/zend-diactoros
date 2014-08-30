# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release..

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
