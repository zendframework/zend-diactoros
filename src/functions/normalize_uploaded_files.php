<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Normalize uploaded files
 *
 * Transforms each value into an UploadedFile instance, and ensures that nested
 * arrays are normalized.
 *
 * @param array $files
 * @return UploadedFileInterface[]
 * @throws InvalidArgumentException for unrecognized values
 */
function normalizeUploadedFiles(array $files)
{
    $normalized = [];
    foreach ($files as $key => $value) {
        if ($value instanceof UploadedFileInterface) {
            $normalized[$key] = $value;
            continue;
        }

        if (is_array($value) && isset($value['tmp_name']) && is_array($value['tmp_name'])) {
            $normalized[$key] = normalizeUploadedFileSpecification($value);
            continue;
        }

        if (is_array($value) && isset($value['tmp_name'])) {
            $normalized[$key] = createUploadedFile($value);
            continue;
        }

        if (is_array($value)) {
            $normalized[$key] = normalizeUploadedFiles($value);
            continue;
        }

        throw new InvalidArgumentException('Invalid value in files specification');
    }
    return $normalized;
}
