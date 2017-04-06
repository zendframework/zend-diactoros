# Serialization

## String

At times, it's useful to either create a string representation of a message (serialization), or to
cast a string or stream message to an object (deserialization). This package provides features for
this in `Zend\Diactoros\Request\Serializer` and `Zend\Diactoros\Response\Serializer`; each provides
the following static methods:

- `fromString($message)` will create either a `Request` or `Response` instance (based on the
  serializer used) from the string message.
- `fromStream(Psr\Http\Message\StreamInterface $stream)` will create either a `Request` or
  `Response` instance (based on the serializer used) from the provided stream.
- `toString(Psr\Http\Message\RequestInterface|Psr\Http\Message\ResponseInterface $message)` will
  create either a string from the provided message.

The deserialization methods (`from*()`) will raise exceptions if errors occur while parsing the
message. The serialization methods (`toString()`) will raise exceptions if required data for
serialization is not present in the message instance.

## Array

This package also provides features for array serialization using
`Zend\Diactoros\Request\ArraySerializer` and `Zend\Diactoros\Response\ArraySerializer`; each provides
the following static methods:

- `fromArray(array $message)` will create either a `Request` or `Response` instance (based on the
  serializer used) from the array message.
- `toArray(Psr\Http\Message\RequestInterface|Psr\Http\Message\ResponseInterface $message)` will
  create an array from the provided message.

The deserialization methods (`fromArray()`) will raise exceptions if errors occur while parsing the
message.

### Example usage

Array serialization can be usesful for log messages:

```php
class LoggerMiddleware
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $response = $next($request, $response);

        $this->logger->debug('Request/Response', [
            'request' => \Zend\Diactoros\Request\ArraySerializer::toArray($request),
            'response' => \Zend\Diactoros\Response\ArraySerializer::toArray($response),
        ]);

        return $response;
    }
}
```
