phly/http
=========

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phly/http/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phly/http/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/phly/http/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/phly/http/?branch=master)
[![Scrutinizer Build Status](https://scrutinizer-ci.com/g/phly/http/badges/build.png?b=master)](https://scrutinizer-ci.com/g/phly/http/build-status/master)

`phly/http` is a PHP package containing implementations of the [proposed PSR HTTP message interfaces](https://github.com/php-fig/fig-standards/blob/master/proposed/http-message.md), as well as a "server" implementation similar to [node's http.Server](http://nodejs.org/api/http.html).

This package exists:

- to provide a proof-of-concept of the proposed PSR HTTP message interfaces with relation to server-side applications.
- to provide a node-like paradigm for PHP front controllers.
- to provide a common methodology for marshaling a request from the server environment.

Installation and Requirements
-----------------------------

Install this library using composer:

```console
$ composer require "psr/http-message:~0.2.0@dev" "phly/http:~1.0-dev@dev"
```

`phly/http` has the following dependencies (which are managed by Composer):

- `psr/http-message`, which defines interfaces for HTTP messages, including requests and responses. `phly/http` provides implementations of each of these.

Contributing
------------

- Please write unit tests for any features or bug reports you have.
- Please run unit tests before opening a pull request. You can do so using `./vendor/bin/phpunit`.
- Please run CodeSniffer before opening a pull request, and correct any issues. Use the following to run it: `./vendor/bin/phpcs --standard=PSR2 --ignore=test/Bootstrap.php src test`.

Usage
-----

Typically, you will consume the Request and Response implementations directly in your applications.

### Marshaling the request

PHP contains a plethora of information about the incoming request, and keeps that information in a variety of locations. `Phly\Http\RequestFactory::fromServer()` can simplify marshaling that information into a request instance.

If you do not yet have a request instance, pass it only the `$_SERVER` superglobal:

```php
$request = Phly\Http\RequestFactory::fromServer($_SERVER);
```

If you already have a request instance, pass that as the second parameter:

```php
// Assignment isn't necessary, as the method will write to the request you provide
$request = Phly\Http\RequestFactory::fromServer($_SERVER, $request);
```

### Manipulating the response

Use the response object to add headers and provide content for the response.

```php
// Write to the response body:
$response->getBody()->write("some content\n");

// Multiple calls to write() append:
$response->getBody()->write("more content\n"); // now "some content\nmore content\n"

// Add headers
// Note: headers do not need to be added before data is written to the body!
$response->setHeader('Content-Type', 'text/plain');
$response->addHeader('X-Show-Something', 'something');
```

### "Serving" an application

`Phly\Http\Server` mimics a portion of the API of node's http.Server class. You can create a server in one of three ways:

```php
// Direct instantiation, with a callback handler, request, and response
$server = new Phly\Http\Server(
    function ($request, $response, $done) {
        $response->getBody()->write("Hello world!");
    },
    $request,
    $response
);

// Using the createServer factory, and providing it $_SERVER:
$server = Phly\Http\Server::createServer(
    function ($request, $response, $done) {
        $response->getBody()->write("Hello world!");
    },
    $_SERVER
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
$server->listen(function ($error = null) {
    if (! $error) {
        return;
    }
    // do something with the error...
});
```

Typically, the listen callback will be an error handler, and can expect to receive the error as its argument.

API
---

### Request Message

`Phly\Http\Request` implements `Psr\Http\Message\RequestInterface`, and includes the following methods:

```php
class Request
{
    public function __construct($protocol = '1.1', $stream = 'php://input');
    public function addHeader($name, $value);
    public function addHeaders(array $headers);
    public function getBody(); // returns a Stream
    public function getHeader();
    public function getHeaderAsArray();
    public function getHeaders();
    public function getMethod();
    public function getProtocolVersion();
    public function getUrl(); // returns a Uri object
    public function removeHeader($name);
    public function setBody(Psr\Http\Message\StreamInterface $stream);
    public function setHeader($name, $value);
    public function setHeaders(array $headers);
    public function setMethod($method);
    public function setUrl($url); // string or Uri object
}
```

Additionally, `Request` implements property overloading, allowing the developer to set and retrieve arbitrary properties other than those exposed via getters. This allows the ability to pass values between handlers, if handlers implement a stack.

I recommend you store values in properties named after your handlers; use arrays or objects in cases where multiple values may be possible.

#### RequestFactory

This static class can be used to marshal a `Request` instance from the PHP environment. The primary entry point is `Phly\Http\RequestFactory::fromServer(array $server, RequestInterface $request = null)`. This method allows you to either marshal a new request instance, or to populate an existing instance (for example, if you are using another `Psr\Http\Message\RequestInterface`-compatible implementation). Examples of usage are:

```php
$request = RequestFactory::fromServer($_SERVER); // returns new Request instance

// or

$request = RequestFactory::fromServer($_SERVER, $request); // returns same request, but populated
```

### Response Message

`Phly\Http\Response` implements `Psr\Http\Message\ResponseInterface`, and includes the following methods:

```php
class Response
{
    public function __construct($stream = 'php://input');
    public function addHeader($name, $value);
    public function addHeaders(array $headers);
    public function getBody(); // returns a Stream
    public function getHeader();
    public function getHeaderAsArray();
    public function getHeaders();
    public function getStatusCode();
    public function getReasonPhrase();
    public function removeHeader($name);
    public function setBody(Psr\Http\Message\StreamInterface $stream);
    public function setHeader($name, $value);
    public function setHeaders(array $headers);
    public function setStatusCode($code);
    public function setReasonPhrase($phrase);
}
```

### URI

`Phly\Http\Uri` models and validates URIs. The request object casts URLs to `Uri` objects, and returns them from `getUrl()`, giving an OOP interface to the parts of a URI. It implements `__toString()`, allowing it to be represented as a string and `echo()`'d directly. The following methods are pertinent:

```php
class Uri
{
    public static function fromArray(array $parts);
    public function __construct($uri);
    public function isValid();
    public function setPath($path);
}
```

`fromArray()` expects an array of URI parts, and should contain 1 or more of the following keys:

- scheme
- host
- port
- path
- query
- fragment

`setPath()` accepts a path, but does not actually change the `Uri` instance; it instead returns a clone of the current instance with the new path.

The following properties are exposed for read-only access:

- scheme
- host
- port
- path
- query
- fragment

### Stream

`Phly\Http\Stream` is an implementation of `Psr\Http\Message\StreamableInterface`, and provides a number of facilities around manipulating the composed PHP stream resource. The constructor accepts a stream, which may be either:

- a stream identifier; e.g., `php://input`, a filename, etc.
- a PHP stream resource

If a stream identifier is provided, an optional second parameter may be provided, the file mode by which to `fopen` the stream.

Request objects by default use a `php://input` stream set to read-only; Response objects by default use a `php://memory` with a mode of `wb+`, allowing binary read/write access.

In most cases, you will not interact with the Stream object directly.

### Server

`Phly\Http\Server` represents a server capable of executing a callback. It has four methods:

```php
class Server
{
    public function __construct(
        callable $callback,
        Psr\Http\Message\RequestInterface $request,
        Psr\Http\Message\ResponseInterface $response
    );
    public static function createServer(
        callable $callback,
        array $server // usually $_SERVER
    );
    public static function createServerFromRequest(
        callable $callback,
        Psr\Http\Message\RequestInterface $request,
        Psr\Http\Message\ResponseInterface $response = null
    );
    public function listen(callable $finalHandler = null);
}
```

You can create an instance of the `Server` using any of the constructor, `createServer()`, or `createServerFromRequest()` methods. If you wish to use the default request and response implementations, `createServer($middleware, $_SERVER)` is the recommended option, as this method will also marshal the `Request` object based on the PHP request environment.  If you wish to use your own implementations, pass them to the constructor or `createServerFromRequest()` method (the latter will create a default `Response` instance if you omit it).

`listen()` executes the callback. If a `$finalHandler` is provided, it will be passed as the third argument to the `$callback` registered with the server.
