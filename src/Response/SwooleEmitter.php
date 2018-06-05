<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Response;

use Psr\Http\Message\ResponseInterface;
use swoole_http_response;

/**
 * @deprecated since 1.8.0. The package zendframework/zend-httphandlerrunner
 *     now provides this functionality.
 */
class SwooleEmitter implements EmitterInterface
{
    use SapiEmitterTrait;

    // @see https://www.swoole.co.uk/docs/modules/swoole-http-server/methods-properties#swoole-http-response-write
    const CHUNK_SIZE = 2097152; // 2 MB

    private $swooleResponse;

    public function __construct(swoole_http_response $swooleResponse)
    {
        $this->swooleResponse = $swooleResponse;
    }

    /**
     * Emits a response for the Swoole environment.
     *
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response)
    {
        $this->emitStatusCode($response);
        $this->emitHeaders($response);
        $this->emitBody($response);
    }

    /**
     * Emit the message body.
     *
     * @param ResponseInterface $response
     */
    private function emitBody(ResponseInterface $response)
    {
        $body = $response->getBody();
        $body->rewind();
        if ($body->getSize() > static::CHUNK_SIZE) {
            while (! $body->eof()) {
                $this->swooleResponse->write($body->read(static::CHUNK_SIZE));
            }
            $this->swooleResponse->end();
        } else {
            $this->swooleResponse->end($body->getContents());
        }
    }

    /**
     * Emit the headers
     *
     * @param ResponseInterface $response
     */
    private function emitHeaders(ResponseInterface $response)
    {
        foreach ($response->getHeaders() as $name => $values) {
            $name = $this->filterHeader($name);
            $this->swooleResponse->header($name, implode(', ', $values));
        }
    }

    /**
     * Emit the status code
     *
     * @param ResponseInterface $response
     */
    private function emitStatusCode(ResponseInterface $response)
    {
        $this->swooleResponse->status($response->getStatusCode());
    }
}
