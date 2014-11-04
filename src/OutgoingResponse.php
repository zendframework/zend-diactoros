<?php
namespace Phly\Http;

use InvalidArgumentException;
use Psr\Http\Message\OutgoingResponseInterface;
use Psr\Http\Message\StreamableInterface;

/**
 * Outgoing HTTP response encapsulation.
 *
 * An outgoing HTTP response is one sent by a server-side application as the
 * result of processing an IncomingRequest. Outgoing responses are mutable to
 * allow developers to iterably build the response.
 */
class OutgoingResponse implements OutgoingResponseInterface
{
    use WritableMessageTrait, ResponseTrait;

    /**
     * @param string|resource|StreamableInterface $stream Stream identifier and/or actual stream resource
     */
    public function __construct($stream = 'php://memory')
    {
        if (! is_string($stream) && ! is_resource($stream) && ! $stream instanceof StreamableInterface) {
            throw new InvalidArgumentException(
                'Stream must be a string stream resource identifier, '
                . 'an actual stream resource, '
                . 'or a Psr\Http\Message\StreamableInterface implementation'
            );
        }

        if (! $stream instanceof StreamableInterface) {
            $stream = new Stream($stream, 'wb+');
        }

        $this->setBody($stream);
    }

    /**
     * Sets the status code of this response.
     *
     * @param integer $code The 3-digit integer result code to set.
     * @param null|string $reasonPhrase The reason phrase to use with the status provided;
     *     if none is provided, and the status has a match in ResponseTrait::$phrases, the
     *     corresponding value will be used.
     * @return void
     */
    public function setStatus($code, $reasonPhrase = null)
    {
        if (! is_int($code)
            || (100 > $code || 599 < $code)
        ) {
            throw new InvalidArgumentException('Status code must be between 100 and 599, inclusive');
        }

        $this->statusCode   = $code;
        $this->reasonPhrase = $reasonPhrase;
    }
}
