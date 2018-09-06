# Emitting responses

> ## Deprecated
>
> Emitters are deprecated from Diactoros starting with version 1.8.0. The
> functionality is now available for any PSR-7 implementation via the package
> [zendframework/zend-httphandlerrunner](https://docs.zendframework.com/zend-httphandlerrunner).
> We suggest using that functionality instead.

If you are using a non-SAPI PHP implementation and wish to use the `Server` class, or if you do not
want to use the `Server` implementation but want to emit a response, this package provides an
interface, `Zend\Diactoros\Response\EmitterInterface`, defining a method `emit()` for emitting the
response.

Diactoros provides two implementations currently, both for working with
traditional Server API (SAPI) implementations: `Zend\Diactoros\Response\SapiEmitter`
and `Zend\Diactoros\Response\SapiStreamEmitter`.  Each uses the native `header()`
PHP function to emit headers, and `echo()` to emit the response body.

If you are using a non-SAPI implementation, you will need to create your own
`EmitterInterface` implementation.

For example, the `SapiEmitter` implementation of the `EmitterInterface` can be used thus:

```php
$response = new Zend\Diactoros\Response();
$response->getBody()->write("some content\n");
$emitter = new Zend\Diactoros\Response\SapiEmitter();
$emitter->emit($response);
```

## Emitting ranges of streamed files

The `SapiStreamEmitter` is useful when you want to emit a `Content-Range`. As an
example, to stream a range of bytes from a file to a client, the client can pass
the following header:

```http
Range: bytes=1024-2047
```

Your application would then populate the response with a `Content-Range` header:

```php
$range = $request->getHeaderLine('range');
$range = str_replace('=', ' ', $range);

$body = new Stream($pathToFile);
$size = $body->getSize();
$range .= '/' . $size;

$response = new Response($body);
$response = $response->withHeader('Content-Range', $range);
```

> Note: you will likely want to ensure the range specified falls within the
> content size of the streamed body!

The `SapiStreamEmitter` detects the `Content-Range` header and emits only the
bytes specified.

```php
$emitter = new SapiStreamEmitter();
$emitter->emit($response);
```

The `SapiStreamEmitter` may be used in place of the `SapiEmitter`, even when not
sending files. However, unlike the `SapiEmitter`, it will emit a chunk of
content at a time instead of the full content at once, which could lead to
performance overhead. The default chunk size is 8192 bytes.
