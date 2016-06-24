# Emitting responses

If you are using a non-SAPI PHP implementation and wish to use the `Server` class, or if you do not
want to use the `Server` implementation but want to emit a response, this package provides an
interface, `Zend\Diactoros\Response\EmitterInterface`, defining a method `emit()` for emitting the
response. A single implementation is currently available, `Zend\Diactoros\Response\SapiEmitter`,
which will use the native PHP functions `header()` and `echo` in order to emit the response. If you
are using a non-SAPI implementation, you will need to create your own `EmitterInterface`
implementation.

For example, the `SapiEmitter` implementation of the `EmitterInterface` can be used thus:

```php
$response = new Zend\Diactoros\Response();
$response->getBody()->write("some content\n");
$emitter = new Zend\Diactoros\Response\SapiEmitter();
$emitter->emit($response);
```
