# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 1.1.0 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [#43](https://github.com/zendframework/zend-diactoros/pull/43) removed both
  `ServerRequestFactory::marshalUri()` and `ServerRequestFactory::marshalHostAndPort()`,
  which were deprecated prior to the 1.0 release.

### Fixed

- Nothing.

## 1.0.2 - 2015-06-04

### Added

- [#27](https://github.com/zendframework/zend-diactoros/pull/27) adds phonetic
  pronunciation of "Diactoros" to the README file.
- [#36](https://github.com/zendframework/zend-diactoros/pull/36) adds property
  annotations to the class-level docblock of `Zend\Diactoros\RequestTrait` to
  ensure properties inherited from the `MessageTrait` are inherited by
  implementations.

### Deprecated

- Nothing.

### Removed

- Nothing.
-
### Fixed

- [#41](https://github.com/zendframework/zend-diactoros/pull/41) fixes the
  namespace for test files to begin with `ZendTest` instead of `Zend`.
- [#46](https://github.com/zendframework/zend-diactoros/pull/46) ensures that
  the cookie and query params for the `ServerRequest` implementation are
  initialized as arrays.
- [#47](https://github.com/zendframework/zend-diactoros/pull/47) modifies the
  internal logic in `HeaderSecurity::isValid()` to use a regular expression
  instead of character-by-character comparisons, improving performance.

## 1.0.1 - 2015-05-26

### Added

- [#10](https://github.com/zendframework/zend-diactoros/pull/10) adds
  `Zend\Diactoros\RelativeStream`, which will return stream contents relative to
  a given offset (i.e., a subset of the stream).  `AbstractSerializer` was
  updated to create a `RelativeStream` when creating the body of a message,
  which will prevent duplication of the stream in-memory.
- [#21](https://github.com/zendframework/zend-diactoros/pull/21) adds a
  `.gitattributes` file that excludes directories and files not needed for
  production; this will further minify the package for production use cases.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#9](https://github.com/zendframework/zend-diactoros/pull/9) ensures that
  attributes are initialized to an empty array, ensuring that attempts to
  retrieve single attributes when none are defined will not produce errors.
- [#14](https://github.com/zendframework/zend-diactoros/pull/14) updates
  `Zend\Diactoros\Request` to use a `php://temp` stream by default instead of
  `php://memory`, to ensure requests do not create an out-of-memory condition.
- [#15](https://github.com/zendframework/zend-diactoros/pull/15) updates
  `Zend\Diactoros\Stream` to ensure that write operations trigger an exception
  if the stream is not writeable. Additionally, it adds more robust logic for
  determining if a stream is writeable.

## 1.0.0 - 2015-05-21

First stable release, and first release as `zend-diactoros`.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
