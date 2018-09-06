# Migration to version 2

If you are only using the PSR-7 implementations (e.g., `Request`, `Response`,
`ServerRequest`, etc.), migrating to v2 can be done by updating your
zendframework/zend-diactoros constraint in your `composer.json`. You have two
options for doing so:

- Adopt the v2 release specifically:

  ```bash
  $ composer require "zendframework/zend-diactoros:^2.0"
  ```

- Update your constraint to allow either version:
  
  - Edit the constraint in your `composer.json` to read:

    ```json
    "zendframework/zend-diactoros": "^1.8.6 || ^2.0"
    ```

  - Update your dependencies:

    ```bash
    $ composer update
    ```

The first approach may fail if libraries you depend on specifically require a
version 1 release. The second approach may leave you on a version 1 release in
situations where other libraries you depend on require version 1.

In all cases, if you are only using the PSR-7 implementations and/or the
`ServerRequestFactory::fromGlobals()` functionality, upgrading to version 2 will
pose no backwards compatibility issues.

## Changed

- `Zend\Diactoros\RequestTrait` now raises an `InvalidArgumentException` in
  `withMethod()` for invalid HTTP method values.

- `Zend\Diactoros\Serializer\Request::toString()` no longer raises an
  `UnexpectedValueException` due to an unexpected HTTP method; this is due to the
  fact that the HTTP method value can no longer be set to an invalid value.

## Removed

Several features were removed for version 2. These include removal of the
`Emitter` functionality, the `Server` implementation, and a number of methods on
the `ServerRequestFactory`.

### Emitters

`Zend\Diactoros\Response\EmitterInterface` and all emitter implementations were
removed from zend-diactoros. They are now available in the
[zendframework/zend-httphandlerrunner package](https://docs.zendframework.com/zend-httphandlerrunner).
In most cases, these can be replaced by changing the namespace of imported
classes from `Zend\Diactoros\Response` to `Zend\HttpHandlerRunner\Emitter`.

### Server

The `Zend\Diactoros\Server` class has been removed. We recommend using the
`RequestHandlerRunner` class from [zendframework/zend-httphandlerrunner](https://docs.zendframework.com/zend-httphandlerrunner)
to provide these capabilities instead. Usage is similar, but the
`RequestHandlerRunner` provides better error handling, and integration with
emitters.

### ServerRequestFactory methods

A number of public static methods have been removed from
`ServerRequestFactory`. The following table details the methods removed, and
replacements you may use if you still require the functionality.

Method Removed                    | Replacement functionality
--------------------------------- | -------------------------
`normalizeServer()`               | `Zend\Diactoros\normalizeServer()`
`marshalHeaders()`                | `Zend\Diactoros\marshalHeadersFromSapi()`
`marshalUriFromServer()`          | `Zend\Diactoros\marshalUriFromSapi()`
`marshalRequestUri()`             | `Uri::getPath()` from the `Uri` instance returned by `marshalUriFromSapi()`
`marshalHostAndPortFromHeaders()` | `Uri::getHost()` and `Uri::getPort()` from the `Uri` instances returned by `marshalUriFromSapi()`
`stripQueryString()`              | `explode("?", $path, 2)[0]`
`normalizeFiles()`                | `Zend\Diactoros\normalizeUploadedFiles()`
