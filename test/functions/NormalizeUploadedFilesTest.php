<?php
/**
 * @see       https://github.com/zendframework/zend-diactoros for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-diactoros/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Diactoros;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

use function Zend\Diactoros\normalizeUploadedFiles;

class NormalizeUploadedFilesTest extends TestCase
{
    public function testCreatesUploadedFileFromFlatFileSpecification()
    {
        $files = [
            'avatar' => [
                'tmp_name' => 'phpUxcOty',
                'name' => 'my-avatar.png',
                'size' => 90996,
                'type' => 'image/png',
                'error' => 0,
            ],
        ];

        $normalised = normalizeUploadedFiles($files);

        $this->assertCount(1, $normalised);
        $this->assertInstanceOf(UploadedFileInterface::class, $normalised['avatar']);
        $this->assertEquals('my-avatar.png', $normalised['avatar']->getClientFilename());
    }

    public function testTraversesNestedFileSpecificationToExtractUploadedFile()
    {
        $files = [
            'my-form' => [
                'details' => [
                    'avatar' => [
                        'tmp_name' => 'phpUxcOty',
                        'name' => 'my-avatar.png',
                        'size' => 90996,
                        'type' => 'image/png',
                        'error' => 0,
                    ],
                ],
            ],
        ];

        $normalised = normalizeUploadedFiles($files);

        $this->assertCount(1, $normalised);
        $this->assertEquals('my-avatar.png', $normalised['my-form']['details']['avatar']->getClientFilename());
    }

    public function testTraversesNestedFileSpecificationContainingNumericIndicesToExtractUploadedFiles()
    {
        $files = [
            'my-form' => [
                'details' => [
                    'avatars' => [
                        'tmp_name' => [
                            0 => 'abc123',
                            1 => 'duck123',
                            2 => 'goose123',
                        ],
                        'name' => [
                            0 => 'file1.txt',
                            1 => 'file2.txt',
                            2 => 'file3.txt',
                        ],
                        'size' => [
                            0 => 100,
                            1 => 240,
                            2 => 750,
                        ],
                        'type' => [
                            0 => 'plain/txt',
                            1 => 'image/jpg',
                            2 => 'image/png',
                        ],
                        'error' => [
                            0 => 0,
                            1 => 0,
                            2 => 0,
                        ],
                    ],
                ],
            ],
        ];

        $normalised = normalizeUploadedFiles($files);

        $this->assertCount(3, $normalised['my-form']['details']['avatars']);
        $this->assertEquals('file1.txt', $normalised['my-form']['details']['avatars'][0]->getClientFilename());
        $this->assertEquals('file2.txt', $normalised['my-form']['details']['avatars'][1]->getClientFilename());
        $this->assertEquals('file3.txt', $normalised['my-form']['details']['avatars'][2]->getClientFilename());
    }

    /**
     * This case covers upfront numeric index which moves the tmp_name/size/etc
     * fields further up the array tree
     */
    public function testTraversesDenormalizedNestedTreeOfIndicesToExtractUploadedFiles()
    {
        $files = [
            'slide-shows' => [
                'tmp_name' => [
                    // Note: Nesting *under* tmp_name/etc
                    0 => [
                        'slides' => [
                            0 => '/tmp/phpYzdqkD',
                            1 => '/tmp/phpYzdfgh',
                        ],
                    ],
                ],
                'error' => [
                    0 => [
                        'slides' => [
                            0 => 0,
                            1 => 0,
                        ],
                    ],
                ],
                'name' => [
                    0 => [
                        'slides' => [
                            0 => 'foo.txt',
                            1 => 'bar.txt',
                        ],
                    ],
                ],
                'size' => [
                    0 => [
                        'slides' => [
                            0 => 123,
                            1 => 200,
                        ],
                    ],
                ],
                'type' => [
                    0 => [
                        'slides' => [
                            0 => 'text/plain',
                            1 => 'text/plain',
                        ],
                    ],
                ],
            ],
        ];

        $normalised = normalizeUploadedFiles($files);

        $this->assertCount(2, $normalised['slide-shows'][0]['slides']);
        $this->assertEquals('foo.txt', $normalised['slide-shows'][0]['slides'][0]->getClientFilename());
        $this->assertEquals('bar.txt', $normalised['slide-shows'][0]['slides'][1]->getClientFilename());
    }
}
