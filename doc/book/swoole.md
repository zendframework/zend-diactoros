# Swoole
use Zend\Diactoros\ServerRequestFactory;

[Swoole](https://www.swoole.co.uk/) is an async programming Framework for PHP
that can be used to create high performance HTTP server applications, e.g. web
APIs. We provided the support of Swoole in `zend-diactoros` using two methods to
convert a [Swoole\Http\Request](http://php.net/manual/en/class.swoole-http-request.php)
in a [PSR-7 Request](https://www.php-fig.org/psr/psr-7/#32-psrhttpmessagerequestinterface)
and a [PSR-7 Response](https://www.php-fig.org/psr/psr-7/#33-psrhttpmessageresponseinterface)
in a [Swoole\Http\Response](http://php.net/manual/en/class.swoole-http-response.php).

To convert a Swoole request in PSR-7 we provided the following static function:

```
Zend\Diactoros\ServerRequestFactory::fromSwoole(swoole_http_request $request)
```

Where `$request` is an instance of `swoole_http_request` (alias of
`Swoole\Http\Request`).

To convert a PSR-7 response in a Swoole response we built a specific emitter,
`Zend\Diactoros\Response\SwooleEmitter`. If you can use this emitter instead of
`Zend\Diactoros\Response\SapiEmitter`.

You need to instantiate the `SwooleEmitter` passing a `swoole_http_response`
object in the constructor.

You can execute Swoole with [Expressive](https://getexpressive.org/) using the
following server implementation:

```php
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\SwooleEmitter;

$http = new swoole_http_server("127.0.0.1", 9501);

$http->on("start", function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

// Bootstrap of zend-expressive
$container = require 'config/container.php';
$app = $container->get(\Zend\Expressive\Application::class);

$http->on("request", function ($request, $response) use ($app) {
    $psr7Request  = ServerRequestFactory::fromSwoole($request);
    $psr7Response = $app->handle($psr7Request);

    $emitter = new SwooleEmitter($response);
    $emitter->emit($psr7Response);
});

$http->start();
```
