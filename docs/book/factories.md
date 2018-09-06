# HTTP Message Factories

[PSR-17](https://www.php-fig.org/psr/psr-17/) defines factory interfaces for
creating [PSR-7](https://www.php-fig.org/psr/psr-7/) instances. As of version
2.0.0, Diactoros supplies implementations of each as follows:

- `Zend\Diactoros\RequestFactory`
- `Zend\Diactoros\ResponseFactory`
- `Zend\Diactoros\ServerRequestFactory`
- `Zend\Diactoros\StreamFactory`
- `Zend\Diactoros\UploadedFileFactory`
- `Zend\Diactoros\UriFactory`

The `ServerRequestFactory` continues to define the static method
`fromGlobals()`, but also serves as a PSR-17 implementation.

These classes may be used as described in the specification document for the
purpose of creating Diactoros instances that fulfill PSR-7 typehints.
