# Custom Responses

When developing server-side applications, the message type you're most likely to create manually is
the response. In such cases, the standard signature can be an obstacle to usability. Let's review:

```php
class Response implements ResponseInterface
{
    public function __construct($body = 'php://temp', $status = 200, array $headers = []);
}
```

Some standard use cases, however, make this un-wieldy:

- Returning a response containing HTML; in this case, you likely want to provide the HTML to the
  constructor, not a stream with the HTML injected.
- Returning a response containing JSON; in this case, you likely want to provide the data to
  serialize to JSON, not a stream containing serialized JSON.
- Returning a response with no content; in this case, you don't want to bother with the body at all.
- Returning a redirect response; in this case, you likely just want to specify the target for the
  `Location` header, and optionally the status code.

Starting with version 1.1, Diactoros offers several custom response types for simplifying these
common tasks.

## Text Responses

`Zend\Diactoros\Response\TextResponse` creates a plain text response. It sets the
`Content-Type` header to `text/plain` by default:

```php
$response = new TextResponse('Hello world!');
```

The constructor accepts two additional arguments: a status code and an array of headers.

```php
$response = new TextResponse($text, 200, ['Content-Type' => ['text/csv']]);
```

## HTML Responses

`Zend\Diactoros\Response\HtmlResponse` allows specifying HTML as a payload, and sets the
`Content-Type` header to `text/html` by default:

```php
$response = new HtmlResponse($htmlContent);
```

The constructor allows passing two additional arguments: a status code, and an array of headers.
These allow you to further seed the initial state of the response, as well as to override the
`Content-Type` header if desired:

```php
$response = new HtmlResponse($htmlContent, 200, [ 'Content-Type' => ['application/xhtml+xml']]);
```

Headers must be in the same format as you would provide to the
[Response constructor](api.md#response-message).

## XML Responses

- Since 1.7.0

`Zend\Diactoros\Response\XmlResponse` allows specifying XML as a payload, and sets the
`Content-Type` header to `application/xml` by default:

```php
$response = new XmlResponse($xml);
```

The constructor allows passing two additional arguments: a status code, and an array of headers.
These allow you to further seed the initial state of the response, as well as to override the
`Content-Type` header if desired:

```php
$response = new XmlResponse($xml, 200, [ 'Content-Type' => ['application/hal+xml']]);
```

Headers must be in the same format as you would provide to the
[Response constructor](api.md#response-message).

## JSON Responses

`Zend\Diactoros\Response\JsonResponse` accepts a data structure to convert to JSON, and sets
the `Content-Type` header to `application/json`:

```php
$response = new JsonResponse($data);
```

If providing an object, we recommend implementing [JsonSerializable](http://php.net/JsonSerializable)
to ensure your object is correctly serialized.

Just like the `HtmlResponse`, the `JsonResponse` allows passing two additional arguments — a
status code, and an array of headers — to allow you to further seed the initial state of the
response:

```php
$response = new JsonResponse($data, 200, [ 'Content-Type' => ['application/hal+json']]);
```

Finally, `JsonResponse` allows a fourth optional argument, the flags to provide to `json_encode()`.
By default, these are set to `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT` (integer
15), providing [RFC 4627](http://tools.ietf.org/html/rfc4627) compliant JSON capable of embedding in
HTML. If you want to specify a different set of flags, use the fourth constructor argument:

```php
$response = new JsonResponse(
    $data,
    200,
    [],
    JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
```

## Empty Responses

Many API actions allow returning empty responses:

- `201 Created` responses are often empty, and only include a `Link` or `Location` header pointing
  to the newly created resource.
- `202 Accepted` responses are typically empty, indicating that the new entity has been received,
  but not yet processed.
- `204 No Content` responses are, by definition, empty, and often used as a success response when
  deleting an entity.

`Zend\Diactoros\Response\EmptyResponse` is a `Zend\Diactoros\Response` extension that, by default,
returns an empty response with a 204 status. Its constructor allows passing the status and headers
only:

```php
class EmptyResponse extends Response
{
    public function __construct($status = 204, array $headers = []);
}
```

An empty, read-only body is injected at instantiation, ensuring no write operations are possible on
the response. Usage is typically one of the following forms:

```php
// Basic 204 response:
$response = new EmptyResponse();

// 201 response with location header:
$response = new EmptyResponse(201, [
    'Location' => [ $url ],
]);

// Alternately, set the header after instantiation:
$response = ( new EmptyResponse(201) )->withHeader('Location', $url);
```

## Redirects

`Zend\Diactoros\Response\RedirectResponse` is a `Zend\Diactoros\Response` extension for producing
redirect responses. The only required argument is a URI, which may be provided as either a string or
`Psr\Http\Message\UriInterface` instance. By default, the status 302 is used, and no other headers
are produced; you may alter these via the additional optional arguments:

```php
class RedirectResponse extends Response
{
    public function __construct($uri, $status = 302, array $headers = []);
}
```

Typical usage is:

```php
// 302 redirect:
$response = new RedirectResponse('/user/login');

// 301 redirect:
$response = new RedirectResponse('/user/login', 301);

// using a URI instance (e.g., by altering the request URI instance)
$uri = $request->getUri();
$response = new RedirectResponse($uri->withPath('/login'));
```

## Creating custom responses

PHP allows constructor overloading. What this means is that constructors of extending classes can
define completely different argument sets without conflicting with the parent implementation.
Considering that most custom response types do not need to change internal functionality, but
instead focus on user experience (i.e., simplifying instantiation), this fact can be leveraged to
create your custom types.

The general pattern will be something like this:

```php
class MyCustomResponse extends Response
{
    public function __construct($data, $status = 200, array $headers = [])
    {
        // - Do something with $data, and create a Stream for the body (if necessary).
        // - Maybe set some default headers.

        parent::__construct($body, $status, $headers);
    }
}
```

Note the call to `parent::__construct()`. This is particularly relevant, as the implementation at
the time of writing has all class properties marked as private, making them inaccessible to
extensions; this is done to protect encapsulation and ensure consistency of operations between
instances.

If you don't want to go the extension route (perhaps you don't want another `ResponseInterface`
implementation within your object graph) you can instead create a factory. As an example:

```php
$plainTextResponse = function ($text, $status = 200, array $headers = []) {
    $response = new Response('php://temp', $status, $headers);
    $response->getBody()->write($text);
    if (! $response->hasHeader('Content-Type')) {
        $response = $response->withHeader('Content-Type', 'text/plain');
    }
    return $response;
};

$response = $plainTextResponse('Hello, world!');
```

We recommend following the semantic of providing the status and headers as the final two arguments
for any factory or custom response extensions.
