# zend-diactoros: HTTP Messages

`zend-diactoros` is a PHP package containing implementations of the [accepted PSR-7 HTTP message
interfaces](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-7-http-message.md), as
well as a "server" implementation similar to [node's http.Server](http://nodejs.org/api/http.html).

This package exists:

- to provide a proof-of-concept of the accepted PSR HTTP message interfaces with relation to
  server-side applications.
- to provide a node-like paradigm for PHP front controllers.
- to provide a common methodology for marshaling a request from the server environment.
