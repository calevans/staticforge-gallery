<?php

declare(strict_types=1);

namespace Calevans\Gallery\Tests\Unit\Shortcodes;

use Calevans\Gallery\Services\GalleryService;
use Calevans\Gallery\Shortcodes\GalleryShortcode;
use EICC\StaticForge\Core\AssetManager;
use EICC\Utils\Container;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class GalleryShortcodeTest extends TestCase
{
    private $root;
    private $galleryService;
    private $container;
    private $assetManager;
    private $shortcode;

    protected function setUp(): void
    {
        $this->root = vfsStream::setup('root');
        
        $this->galleryService = $this->createMock(GalleryService::class);
        $this->container = $this->createMock(Container::class);
        $this->assetManager = $this->createMock(AssetManager::class);
        
        $this->shortcode = new GalleryShortcode($this->galleryService);
        $this->shortcode->setContainer($this->container);
    }

    public function testHandleReturnsErrorIfPathMissing(): void
    {
        $result = $this->shortcode->handle([]);
        $this->assertStringContainsString('Gallery path not specified', $result);
    }

    public function testHandleReturnsErrorIfSourceDirNotConfigured(): void
    {
        $this->container->method('getVariable')->with('SOURCE_DIR')->willReturn(null);
        
        $result = $this->shortcode->handle(['path' => 'test']);
        $this->assertStringContainsString('Source directory not configured', $result);
    }

    public function testHandleUsesDirectPathIfItExists(): void
    {
        $sourceDir = $this->root->url();
        $this->container->method('getVariable')->with('SOURCE_DIR')->willReturn($sourceDir);
        
        // Create directory
        mkdir($sourceDir . '/my-gallery');
        
        $this->galleryService->expects($this->once())
            ->method('getImages')
            ->with($sourceDir, 'my-gallery')
            ->willReturn([['src' => 'img.jpg', 'thumb' => 'img.jpg', 'title' => 'Img', 'alt' => 'Img']]);

        $this->container->method('get')->with(AssetManager::class)->willReturn($this->assetManager);

        $this->shortcode->handle(['path' => 'my-gallery']);
    }

    public function testHandleFallsBackToAssetsImagesIfDirectPathDoesNotExist(): void
    {
        $sourceDir = $this->root->url();
        $this->container->method('getVariable')->with('SOURCE_DIR')->willReturn($sourceDir);
        
        // Create directory in assets/images
        mkdir($sourceDir . '/assets', 0777, true);
        mkdir($sourceDir . '/assets/images', 0777, true);
        mkdir($sourceDir . '/assets/images/my-gallery', 0777, true);
        
        $this->galleryService->expects($this->once())
            ->method('getImages')
            ->with($sourceDir, 'assets/images/my-gallery')
            ->willReturn([['src' => 'img.jpg', 'thumb' => 'img.jpg', 'title' => 'Img', 'alt' => 'Img']]);

        $this->container->method('get')->with(AssetManager::class)->willReturn($this->assetManager);

        $this->shortcode->handle(['path' => 'my-gallery']);
    }
    
    public function testHandleRegistersAssets(): void
    {
        $sourceDir = $this->root->url();
        $this->container->method('getVariable')->with('SOURCE_DIR')->willReturn($sourceDir);
        mkdir($sourceDir . '/my-gallery');
        
        $this->galleryService->method('getImages')->willReturn([['src' => 'img.jpg', 'thumb' => 'img.jpg', 'title' => 'Img', 'alt' => 'Img']]);
        
        $this->container->method('get')->with(AssetManager::class)->willReturn($this->assetManager);
        
        $this->assetManager->expects($this->exactly(2))->method('addStyle');
        $this->assetManager->expects($this->exactly(3))->method('addScript');
        
        $this->shortcode->handle(['path' => 'my-gallery']);
    }
}
