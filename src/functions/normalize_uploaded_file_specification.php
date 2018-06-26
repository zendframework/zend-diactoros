<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Diactoros;

/**
 * Normalize an array of file specifications.
 *
 * Loops through all nested files (as determined by receiving an array to the
 * `tmp_name` key of a `$_FILES` specification) and returns a normalized array
 * of UploadedFile instances.
 *
 * This function should primarily be used for managing a `$_FILES` array
 * representing a nested set of uploaded files as produced by the php-fpm SAPI,
 * CGI SAPI, or mod_php SAPI.
 *
 * @param array $files
 * @return UploadedFile[]
 */
function normalizeUploadedFileSpecification(array $files = [])
{
    if (! isset($files['tmp_name']) || ! is_array($files['tmp_name'])
        || ! isset($files['size']) || ! is_array($files['size'])
        || ! isset($files['error']) || ! is_array($files['error'])
    ) {
        throw new InvalidArgumentException(sprintf(
            '$files provided to %s MUST contain each of the keys "tmp_name",'
            . ' "size", and "error", with each represented as an array;'
            . ' one or more were missing or non-array values',
            __FUNCTION__
        ));
    }


    $normalized = [];
    foreach (array_keys($files['tmp_name']) as $key) {
        $spec = [
            'tmp_name' => $files['tmp_name'][$key],
            'size'     => $files['size'][$key],
            'error'    => $files['error'][$key],
            'name'     => isset($files['name'][$key]) ? $files['name'][$key] : null,
            'type'     => isset($files['type'][$key]) ? $files['type'][$key] : null,
        ];
        $normalized[$key] = createUploadedFile($spec);
    }
    return $normalized;
}
