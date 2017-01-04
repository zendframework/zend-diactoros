<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       http://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros\Response;

use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;
use Zend\Diactoros\Response;

/**
 * Serialize or deserialize response messages.
 *
 * This class provides functionality for serializing a ResponseInterface instance
 * to an array, as well as the reverse operation of creating a Request instance
 * from an array representing a message.
 */
final class ArraySerializer
{
    /**
     * Serialize a response message to an array.
     *
     * @param ResponseInterface $request
     *
     * @return array
     */
    public static function toArray(ResponseInterface $request)
    {
        return [];
    }

    /**
     * Deserialize a response array to a response instance.
     *
     * @param array $serializedResponse
     *
     * @return Response
     *
     * @throws UnexpectedValueException when missing parameters in array.
     */
    public static function fromArray(array $serializedResponse)
    {
    }
}
