# API

## Request Message

`Zend\Diactoros\Request` implements [`Psr\Http\Message\RequestInterface`](https://github.com/php-fig/http-message/blob/master/src/RequestInterface.php),
and is intended for client-side requests. It includes the following methods:

```php
class Request
{
    public function __construct(
        $uri = null,
        $method = null,
        $body = 'php://memory',
        array $headers = []
    );

    // See psr/http-message's RequestInterface for other methods
}
```

Requests are immutable. Any methods that would change state &mdash; those prefixed with `with` and
`without` &mdash; all return a new instance with the changes requested.

## ServerRequest Message

For server-side applications, `Zend\Diactoros\ServerRequest` implements
[`Psr\Http\Message\ServerRequestInterface`](https://github.com/php-fig/http-message/blob/master/src/ServerRequestInterface.php),
which provides access to the elements of an HTTP request, as well as uniform access to the various
elements of incoming data. The methods included are:

```php
class ServerRequest
{
    public function __construct(
        array $serverParams = [],
        array $fileParams = [],
        $uri = null,
        $method = null,
        $body = 'php://input',
        array $headers = []
    );

    // See psr/http-message's ServerRequestInterface for other methods.
}
```

The `ServerRequest` is immutable. Any methods that would change state &mdash; those prefixed with `with`
and `without` &mdash; all return a new instance with the changes requested. Server parameters are
considered completely immutable, however, as they cannot be recalculated, and, rather, is a source
for other values.

## Response Message

`Zend\Diactoros\Response` provides an implementation of
[`Psr\Http\Message\ResponseInterface`](https://github.com/php-fig/http-message/blob/master/src/ResponseInterface.php),
an object to be used to aggregate response information for both HTTP clients and server-side
applications, including headers and message body content. It includes the following:

```php
class Response
{
    public function __construct(
        $body = 'php://memory',
        $statusCode = 200,
        array $headers = []
    );

    // See psr/http-message's ResponseInterface for other methods
}
```

Like the `Request` and `ServerRequest`, responses are immutable. Any methods that would change state
&mdash; those prefixed with `with` and `without` &mdash; all return a new instance with the changes requested.

### HtmlResponse and JsonResponse

- Added in 1.1.0

The most common use case in server-side applications for generating responses is to provide a string
to use for the response, typically HTML or data to serialize as JSON.  `Zend\Diactoros\Response\HtmlResponse` and `Zend\Diactoros\Response\JsonResponse` exist to facilitate these use cases:

```php
$htmlResponse = new HtmlResponse($html);

$jsonResponse = new JsonResponse($data);
```

In the first example, you will receive a response with a stream containing the HTML; additionally,
the `Content-Type` header will be set to `text/html`. In the second case, the stream will contain a
stream containing the JSON-serialized `$data`, and have a `Content-Type` header set to
`application/json`.

Both objects allow passing the HTTP status, as well as any headers you want to specify,
including the `Content-Type` header:

```php
$htmlResponse = new HtmlResponse($html, 404, [
    'Content-Type' => [ 'application/xhtml+xml' ],
]);

$jsonResponse = new JsonResponse($data, 422, [
    'Content-Type' => [ 'application/problem+json' ],
]);
```

## ServerRequestFactory

This static class can be used to marshal a `ServerRequest` instance from the PHP environment. The
primary entry point is `Zend\Diactoros\ServerRequestFactory::fromGlobals(array $server, array
$query, array $body, array $cookies, array $files)`. This method will create a new `ServerRequest`
instance with the data provided. Examples of usage are:

```php
// Returns new ServerRequest instance, using values from superglobals:
$request = ServerRequestFactory::fromGlobals();

// or

// Returns new ServerRequest instance, using values provided (in this
// case, equivalent to the previous!)
$request = RequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);
```

### ServerRequestFactory helper functions

- Since 1.8.0

In order to create the various artifacts required by a `ServerRequest` instance,
Diactoros also provides a number of functions under the `Zend\Diactoros`
namespace for introspecting the SAPI `$_SERVER` parameters, headers, `$_FILES`,
and even the `Cookie` header. These include:

- `Zend\Diactoros\normalizeServer(array $server, callable $apacheRequestHeaderCallback = null) : array`
  (its main purpose is to aggregate the `Authorization` header in the SAPI params
  when under Apache)
- `Zend\Diactoros\marshalProtocolVersionFromSapi(array $server) : string`
- `Zend\Diactoros\marshalMethodFromSapi(array $server) : string`
- `Zend\Diactoros\marshalUriFromSapi(array $server, array $headers) : Uri`
- `Zend\Diactoros\marshalHeadersFromSapi(array $server) : array`
- `Zend\Diactoros\parseCookieHeader(string $header) : array`
- `Zend\Diactoros\createUploadedFile(array $spec) : UploadedFile` (creates the
  instance from a normal `$_FILES` entry)
- `Zend\Diactoros\normalizeUploadedFiles(array $files) : UploadedFileInterface[]`
  (traverses a potentially nested array of uploaded file instances and/or
  `$_FILES` entries, including those aggregated under mod_php, php-fpm, and
  php-cgi in order to create a flat array of `UploadedFileInterface` instances
  to use in a request)

## URI

`Zend\Diactoros\Uri` is an implementation of
[`Psr\Http\Message\UriInterface`](https://github.com/php-fig/http-message/blob/master/src/UriInterface.php),
and models and validates URIs. It implements `__toString()`, allowing it to be represented as a
string and `echo()`'d directly. The following methods are pertinent:

```php
class Uri
{
    public function __construct($uri = '');

    // See psr/http-message's UriInterface for other methods.
}
```

Like the various message objects, URIs are immutable. Any methods that would
change state &mdash; those
prefixed with `with` and `without` &mdash; all return a new instance with the changes requested.

## Stream

`Zend\Diactoros\Stream` is an implementation of
[`Psr\Http\Message\StreamInterface`](https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php),
and provides a number of facilities around manipulating the composed PHP stream resource. The
constructor accepts a stream, which may be either:

- a stream identifier; e.g., `php://input`, a filename, etc.
- a PHP stream resource

If a stream identifier is provided, an optional second parameter may be provided, the file mode by
which to `fopen` the stream.

`ServerRequest` objects by default use a `php://input` stream set to read-only; `Response` objects
by default use a `php://memory` with a mode of `wb+`, allowing binary read/write access.

In most cases, you will not interact with the Stream object directly.

## UploadedFile

`Zend\Diactoros\UploadedFile` is an implementation of
[`Psr\Http\Message\UploadedFileInterface`](https://github.com/php-fig/http-message/blob/master/src/UploadedFileInterface.php),
and provides abstraction around a single uploaded file, including behavior for interacting with it
as a stream or moving it to a filesystem location.

In most cases, you will only use the methods defined in the `UploadedFileInterface`.

## Server

> ### Deprecated
>
> The class `Zend\Diactoros\Server` is deprecated as of the 1.8.0 release. We
> recommend using the class `Zend\HttpHandlerRunner\RequestHandlerRunner` via
> the package [zendframework/zend-httphandlerrunner](https://docs.zendframework.com/zend-httphandlerrunner)
> instead.

`Zend\Diactoros\Server` represents a server capable of executing a callback. It has four methods:

```php
class Server
{
    public function __construct(
        callable $callback,
        Psr\Http\Message\ServerRequestInterface $request,
        Psr\Http\Message\ResponseInterface $response
    );
    public static function createServer(
        callable $callback,
        array $server,  // usually $_SERVER
        array $query,   // usually $_GET
        array $body,    // usually $_POST
        array $cookies, // usually $_COOKIE
        array $files    // usually $_FILES
    );
    public static function createServerFromRequest(
        callable $callback,
        Psr\Http\Message\ServerRequestInterface $request,
        Psr\Http\Message\ResponseInterface $response = null
    );
    public function setEmitter(Response\EmitterInterface $emitter);
    public function listen(callable $finalHandler = null);
}
```

You can create an instance of the `Server` using any of the constructor, `createServer()`, or
`createServerFromRequest()` methods. If you wish to use the default request and response
implementations, `createServer($middleware, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES)` is the
recommended option, as this method will also marshal the `ServerRequest` object based on the PHP
request environment.  If you wish to use your own implementations, pass them to the constructor or
`createServerFromRequest()` method (the latter will create a default `Response` instance if you omit
it).

`listen()` executes the callback. If a `$finalHandler` is provided, it will be passed as the third
argument to the `$callback` registered with the server.
