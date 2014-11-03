<?php
namespace Phly\Http;

use InvalidArgumentException;

/**
 * Mixin to IncomingResponse and IncomingRequest to allow setting all headers
 * at once, in the format that the MessageTrait defines.
 */
trait ImmutableHeadersTrait
{
    /**
     * Set the header values.
     * 
     * @param array $headers 
     * @return void
     * @throws InvalidArgumentException if any value is invalid.
     */
    private function setHeaders(array $headers)
    {
        foreach ($headers as $header => $values) {
            $header = strtolower($header);

            if (is_string($values)) {
                $this->headers[$header] = [ $values ];
                continue;
            }

            if (! is_array($values)) {
                throw new InvalidArgumentException(sprintf(
                    'Value for header "%s" is not a string or array of strings',
                    $header
                ));
            }

            if (! $this->headerValuesAreStrings($values)) {
                throw new InvalidArgumentException(sprintf(
                    'One or more values in the header "%s" are not strings',
                    $header
                ));
            }

            $this->headers[$header] = $values;
        }
    }

    /**
     * Are all header values provided strings?
     * 
     * @param array $values 
     * @return bool
     */
    private function headerValuesAreStrings(array $values)
    {
        foreach ($values as $value) {
            if (! is_string($value)) {
                return false;
            }
        }
        return true;
    }
}
