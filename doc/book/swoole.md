# Swoole

[Swoole](https://www.swoole.co.uk/) is an async programming Framework for PHP
that can be used to create high performance HTTP server applications, e.g. web
APIs. We provided the support of Swoole in `zend-diactoros` using a static
method to convert a [Swoole\Http\Request](http://php.net/manual/en/class.swoole-http-request.php)
in a [PSR-7 Request](https://www.php-fig.org/psr/psr-7/#32-psrhttpmessagerequestinterface).

This method is as follows:

```
Zend\Diactoros\ServerRequestFactory::fromSwoole(swoole_http_request $request)
```

Where `$request` is an instance of `swoole_http_request` (alias of
`Swoole\Http\Request`).
