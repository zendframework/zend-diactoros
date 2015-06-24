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
  seriazlize to JSON, not a stream containing serialized JSON.
- Returning a response with no content; in this case, you don't want to bother with the body at all.
- Returning a redirect response; in this case, you likely just want to specify the target for the
  `Location` header, and optionally the status code.

Starting with version 1.1, Diactoros offers several custom response types and factories for
simplifying these common tasks.

## HTML responses

`Zend\Diactoros\Response\HtmlResponse` is a `Zend\Diactoros\Response` extension for producing
HTML responses. The only required argument is the HTML body, which may be provided as either a string or
`Psr\Http\Message\StreamInterface` instance. By default, the status 200 is used, and 'text/html' content-type
is used; you may alter these via the additional optional arguments:

```php
class HtmlResponse extends Response
{
    public function __construct($body, $status = 200, array $headers = []);
}
```

Typical usage is:

```php
$response = new HtmlResponse('<html><body>Hello world!</body></html>');
```

The constructor allows passing two additional arguments: a status code, and an array of headers. These
allow you to further seed the initial state of the response.

Headers must be in the same format as you would provide to the
[Response constructor][api.md#response-message].

## JSON responses

`Zend\Diactoros\Response\JsonResponse` is a `Zend\Diactoros\Response` extension for producing
JSON responses. The only required argument is the data that will be serialized as a JSON string. 
By default, the status 200 is used, and 'application/json' content-type
is used; you may alter these via the additional optional arguments:

```php
class HtmlResponse extends Response
{
    public function __construct($data, $status = 200, array $headers = [], $encodingOptions = 15);
}
```

If providing an object, we recommend implementing
[JsonSerializable](http://php.net/JsonSerializable) to ensure your object is correctly serialized.

Typical usage is:

```php
$response = new JsonResponse(array("hello" => "world");
```

The constructor allows passing three additional arguments: 

- A status code
- An array of headers. Headers must be in the same format as you would provide to the
  [Response constructor][api.md#response-message].
- The JSON encoding options. These options default to JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP 
  | JSON_HEX_QUOT (RFC4627-compliant JSON, which may also be embedded into HTML)

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
implementation within your object graph) you can instead create a factory. 
[StringResponse](https://github.com/zendframework/zend-diactoros/tree/master/src/Response/StringResponse.php)
provides one such example. We recommend the following semantics:

```php
function ($dataOrMessage, $status = 200, array $headers = []);
```

These ensure consistency of factories, and allow consumers to provide the status and
instance-specific headers on creation. (Obviously, specify different defaults as necessary.)
