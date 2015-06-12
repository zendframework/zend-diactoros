# Serialization

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
