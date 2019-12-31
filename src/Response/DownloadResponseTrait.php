<?php


namespace Zend\Diactoros\Response;

use InvalidArgumentException;
use function array_keys;
use function array_merge;
use function implode;
use function in_array;

trait DownloadResponseTrait
{

    /**
     * @var string The filename to be sent with the response
     */
    private $filename;

    /**
     * @var string The content type to be sent with the response
     */
    private $contentType;

    /**
     * A list of header keys required to be sent with a download response
     *
     * @var array
     */
    private $downloadResponseHeaders = [
        'cache-control',
        'content-description',
        'content-disposition',
        'content-transfer-encoding',
        'expires',
        'pragma'
    ];

    /**
     * Get download headers
     *
     * @return array
     */
    private function getDownloadHeaders(): array
    {
        $headers = [];
        $headers['cache-control'] = 'must-revalidate';
        $headers['content-description'] = 'File Transfer';
        $headers['content-disposition'] = sprintf('attachment; filename=%s', self::DEFAULT_DOWNLOAD_FILENAME);
        $headers['content-transfer-encoding'] = 'Binary';
        $headers['content-type'] = 'application/octet-stream';
        $headers['expires'] = '0';
        $headers['pragma'] = 'Public';

        return $headers;
    }

    /**
     * Check if the extra headers contain any of the download headers
     *
     * The download headers cannot be overridden.
     *
     * @param array $downloadHeaders
     * @param array $headers
     * @return bool
     */
    public function overridesDownloadHeaders(array $downloadHeaders, array $headers = []) : bool
    {
        $overridesDownloadHeaders = false;

        foreach (array_keys($headers) as $header) {
            if (in_array($header, $downloadHeaders)) {
                $overridesDownloadHeaders = true;
                break;
            }
        }

        return $overridesDownloadHeaders;
    }

    /**
     * Prepare download response headers
     *
     * This function prepares the download response headers. It does so by:
     * - Merging the optional with over the default ones (the default ones cannot be overridden)
     * - Set the content-type and content-disposition headers from $filename and $contentType passed
     *   to the constructor.
     *
     * @param array $headers
     * @return array
     * @throws InvalidArgumentException if an attempt is made to override a default header
     */
    private function prepareDownloadHeaders(array $headers = []) : array
    {
        if ($this->overridesDownloadHeaders($this->downloadResponseHeaders, $headers)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot override download headers (%s) when download response is being sent',
                    implode(', ', $this->downloadResponseHeaders)
                )
            );
        }

        return array_merge(
            $headers,
            $this->getDownloadHeaders(),
            [
                'content-disposition' => sprintf('attachment; filename=%s', $this->filename),
                'content-type' => $this->contentType,
            ]
        );
    }
}
