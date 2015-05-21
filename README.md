phly/http
=========

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phly/http/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phly/http/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/phly/http/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/phly/http/?branch=master)
[![Scrutinizer Build Status](https://scrutinizer-ci.com/g/phly/http/badges/build.png?b=master)](https://scrutinizer-ci.com/g/phly/http/build-status/master)

`phly/http` is a PHP package containing implementations of the [accepted PSR-7 HTTP message interfaces](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-7-http-message.md), as well as a "server" implementation similar to [node's http.Server](http://nodejs.org/api/http.html).

This package exists:

- to provide a proof-of-concept of the proposed PSR HTTP message interfaces with relation to server-side applications.
- to provide a node-like paradigm for PHP front controllers.
- to provide a common methodology for marshaling a request from the server environment.

Installation and Requirements
-----------------------------

Install this library using composer:

```console
$ composer require phly/http
```

`phly/http` has the following dependencies (which are managed by Composer):

- `psr/http-message`, which defines interfaces for HTTP messages, including requests and responses. `phly/http` provides implementations of each of these.

Usage
-----

Usage will differ based on whether you are writing an HTTP client, or a server-side application.

For HTTP client purposes, you will create and populate a `Request` instance, and the client should return a `Response` instance.

For server-side applications, you will create a `ServerRequest` instance, and populate and return a `Response` instance.

### HTTP Clients

A client will _send_ a request, and _return_ a response. As a developer, you will _create_ and _populate_ the request, and then _introspect_ the response.  Both requests and responses are immutable; if you make changes -- e.g., by calling setter methods -- you must capture the return value, as it is a new instance.

```php
// Create a request
$request = (new Phly\Http\Request())
    ->withUri(new Phly\Http\Uri('http://example.com'))
    ->withMethod('PATCH')
    ->withAddedHeader('Authorization', 'Bearer ' . $token)
    ->withAddedHeader('Content-Type', 'application/json');

// OR:
$request = new Phly\Http\Request(
    'http://example.com',
    'PATCH',
    'php://memory',
    [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type'  => 'application/json',
    ]
);

// If you want to set a non-origin-form request target, set the
// request-target explicitly:
$request = $request->withRequestTarget((string) $uri);       // absolute-form
$request = $request->withRequestTarget($uri->getAuthority(); // authority-form
$request = $request->withRequestTarget('*');                 // asterisk-form

// Once you have the instance:
$request->getBody()->write(json_encode($data));
$response = $client->send($request);

printf("Response status: %d (%s)\n", $response->getStatusCode(), $response->getReasonPhrase());
printf("Headers:\n");
foreach ($response->getHeaders() as $header => $values) {
  printf("    %s: %s\n", $header, implode(', ', $values));
}
printf("Message:\n%s\n", $response->getBody());
```

(Note: phly/http does NOT ship with a client implementation; the above is just an illustration of a possible implementation.)

### Server-Side Applications

Server-side applications will need to marshal the incoming request based on superglobals, and will then populate and send a response.

#### Marshaling an incoming request

PHP contains a plethora of information about the incoming request, and keeps that information in a variety of locations. `Phly\Http\ServerRequestFactory::fromGlobals()` can simplify marshaling that information into a request instance.

You can call the factory method with or without the following arguments, in the following order:

- `$server`, typically `$_SERVER`
- `$query`, typically `$_GET`
- `$body`, typically `$_POST`
- `$cookies`, typically `$_COOKIE`
- `$files`, typically `$_FILES`

The method will then return a `Phly\Http\ServerRequest` instance. If any argument is omitted, the associated superglobal will be used.

```php
$request = Phly\Http\ServerRequestFactory::fromGlobals(
  $_SERVER,
  $_GET,
  $_POST,
  $_COOKIE,
  $_FILES
);
```

#### Manipulating the response

Use the response object to add headers and provide content for the response.  Writing to the body does not create a state change in the response, so it can be done without capturing the return value. Manipulating headers does, however.

```php
$response = new Phly\Http\Response();

// Write to the response body:
$response->getBody()->write("some content\n");

// Multiple calls to write() append:
$response->getBody()->write("more content\n"); // now "some content\nmore content\n"

// Add headers
// Note: headers do not need to be added before data is written to the body!
$response = $response
    ->withHeader('Content-Type', 'text/plain')
    ->withAddedHeader('X-Show-Something', 'something');
```

#### "Serving" an application

`Phly\Http\Server` mimics a portion of the API of node's `http.Server` class. It invokes a callback, passing it an `ServerRequest`, an `Response`, and optionally a callback to use for incomplete/unhandled requests.

You can create a server in one of three ways:

```php
// Direct instantiation, with a callback handler, request, and response
$server = new Phly\Http\Server(
    function ($request, $response, $done) {
        $response->getBody()->write("Hello world!");
    },
    $request,
    $response
);

// Using the createServer factory, providing it with the various superglobals:
$server = Phly\Http\Server::createServer(
    function ($request, $response, $done) {
        $response->getBody()->write("Hello world!");
    },
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

// Using the createServerFromRequest factory, and providing it a request:
$server = Phly\Http\Server::createServerfromRequest(
  function ($request, $response, $done) {
      $response->getBody()->write("Hello world!");
  },
  $request
);
```

Server callbacks can expect up to three arguments, in the following order:

- `$request` - the request object
- `$response` - the response object
- `$done` - an optional callback to call when complete

Once you have your server instance, you must instruct it to listen:

```php
$server->listen();
```

At this time, you can optionally provide a callback to `listen()`; this will be passed to the handler as the third argument (`$done`):

```php
$server->listen(function ($request, $response, $error = null) {
    if (! $error) {
        return;
    }
    // do something with the error...
});
```

Typically, the `listen` callback will be an error handler, and can expect to
receive the request, response, and error as its arguments (though the error may
be null).

API
---

### Request Message

`Phly\Http\Request` implements `Psr\Http\Message\RequestInterface`, and is intended for client-side requests. It includes the following methods:

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

Requests are immutable. Any methods that would change state -- those prefixed with `with` and `without` -- all return a new instance with the changes requested.

### ServerRequest Message

For server-side applications, `Phly\Http\ServerRequest` implements `Psr\Http\Message\ServerRequestInterface`, which provides access to the elements of an HTTP request, as well as uniform access to the various elements of incoming data. The methods included are:

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

The `ServerRequest` is immutable. Any methods that would change state -- those prefixed with `with` and `without` -- all return a new instance with the changes requested. Server parameters are considered completely immutable, however, as they cannot be recalculated, and, rather, is a source for other values.

### Response Message

`Phly\Http\Response` provides an implementation of `Psr\Http\Message\ResponseInterface`, an object to be used to aggregate response information for both HTTP clients and server-side applications, including headers and message body content. It includes the following:

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

Like the `Request` and `ServerRequest`, responses are immutable. Any methods that would change state -- those prefixed with `with` and `without` -- all return a new instance with the changes requested.

#### ServerRequestFactory

This static class can be used to marshal a `ServerRequest` instance from the PHP environment. The primary entry point is `Phly\Http\ServerRequestFactory::fromGlobals(array $server, array $query, array $body, array $cookies, array $files)`. This method will create a new `ServerRequest` instance with the data provided. Examples of usage are:

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

### URI

`Phly\Http\Uri` is an implementation of `Psr\Http\Message\UriInterface`, and models and validates URIs. It implements `__toString()`, allowing it to be represented as a string and `echo()`'d directly. The following methods are pertinent:

```php
class Uri
{
    public function __construct($uri = '');

    // See psr/http-message's UriInterface for other methods.
}
```

Like the various message objects, URIs are immutable. Any methods that would change state -- those prefixed with `with` and `without` -- all return a new instance with the changes requested.

### Stream

`Phly\Http\Stream` is an implementation of `Psr\Http\Message\StreamInterface`, and provides a number of facilities around manipulating the composed PHP stream resource. The constructor accepts a stream, which may be either:

- a stream identifier; e.g., `php://input`, a filename, etc.
- a PHP stream resource

If a stream identifier is provided, an optional second parameter may be provided, the file mode by which to `fopen` the stream.

`ServerRequest` objects by default use a `php://input` stream set to read-only; `Response` objects by default use a `php://memory` with a mode of `wb+`, allowing binary read/write access.

In most cases, you will not interact with the Stream object directly.

### UploadedFile

`Phly\Http\UploadedFile` is an implementation of `Psr\Http\Message\UploadedFileInterface`, and provides abstraction around a single uploaded file, including behavior for interacting with it as a stream or moving it to a filesystem location.

In most cases, you will only use the methods defined in the `UploadedFileInterface`.

### Server

`Phly\Http\Server` represents a server capable of executing a callback. It has four methods:

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

You can create an instance of the `Server` using any of the constructor, `createServer()`, or `createServerFromRequest()` methods. If you wish to use the default request and response implementations, `createServer($middleware, $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES)` is the recommended option, as this method will also marshal the `ServerRequest` object based on the PHP request environment.  If you wish to use your own implementations, pass them to the constructor or `createServerFromRequest()` method (the latter will create a default `Response` instance if you omit it).

`listen()` executes the callback. If a `$finalHandler` is provided, it will be passed as the third argument to the `$callback` registered with the server.

## Emitting responses

If you are using a non-SAPI PHP implementation and wish to use the `Server` class, or if you do not want to use the `Server` implementation but want to emit a response, this package provides an interface, `Phly\Http\Response\EmitterInterface`, defining a method `emit()` for emitting the response. A single implementation is currently available, `Phly\Http\Response\SapiEmitter`, which will use the native PHP functions `header()` and `echo` in order to emit the response. If you are using a non-SAPI implementation, you will need to create your own `EmitterInterface` implementation.

## Serialization

At times, it's useful to either create a string representation of a message (serialization), or to cast a string or stream message to an object (deserialization). This package provides features for this in `Phly\Http\Request\Serializer` and `Phly\Http\Response\Serializer`; each provides the following static methods:

- `fromString($message)` will create either a `Request` or `Response` instance (based on the serializer used) from the string message.
- `fromStream(Psr\Http\Message\StreamInterface $stream)` will create either a `Request` or `Response` instance (based on the serializer used) from the provided stream.
- `toString(Psr\Http\Message\RequestInterface|Psr\Http\Message\ResponseInterface $message)` will create either a string from the provided message.

The deserialization methods (`from*()`) will raise exceptions if errors occur while parsing the message. The serialization methods (`toString()`) will raise exceptions if required data for serialization is not present in the message instance.
