<?php

declare(strict_types=1);

namespace Calevans\Gallery\Tests\Unit\Services;

use Calevans\Gallery\Services\GalleryService;
use EICC\Utils\Log;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class GalleryServiceTest extends TestCase
{
    private $root;
    private $logger;
    private $service;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root');
        $this->logger = $this->createMock(Log::class);
        $this->service = new GalleryService($this->logger);
    }

    public function testGetImagesReturnsEmptyArrayIfDirectoryNotFound(): void
    {
        $images = $this->service->getImages($this->root->url(), 'non-existent');
        $this->assertEmpty($images);
    }

    public function testGetImagesReturnsImages(): void
    {
        // Create structure: root/assets/images/my-gallery/image1.jpg
        $structure = [
            'assets' => [
                'images' => [
                    'my-gallery' => [
                        'image1.jpg' => 'content',
                        'image2.png' => 'content',
                        'not-image.txt' => 'content',
                    ]
                ]
            ]
        ];
        vfsStream::create($structure, $this->root);

        $images = $this->service->getImages($this->root->url(), 'assets/images/my-gallery');

        $this->assertCount(2, $images);
        // Note: The service returns paths relative to the site root, not the filesystem root
        
        $this->assertEquals('/assets/images/my-gallery/image1.jpg', $images[0]['src']);
        $this->assertEquals('Image1', $images[0]['title']);
        $this->assertEquals('/assets/images/my-gallery/image2.png', $images[1]['src']);
    }
}
