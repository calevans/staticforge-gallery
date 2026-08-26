<?php

declare(strict_types=1);

namespace Calevans\Gallery\Tests\Unit;

use Calevans\Gallery\Feature;
use Calevans\Gallery\Tests\TestCase;
use EICC\StaticForge\Core\Events\ConsoleInitEvent;
use EICC\StaticForge\Core\Events\Event;
use EICC\StaticForge\Core\EventManager;
use EICC\StaticForge\Core\FeatureFactory;
use EICC\StaticForge\Shortcodes\ShortcodeManager;
use Symfony\Component\Console\Application;

class FeatureTest extends TestCase
{
    private Feature $feature;
    private EventManager $eventManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventManager = new EventManager();

        $feature = (new FeatureFactory($this->container))->make(Feature::class);
        $this->assertInstanceOf(Feature::class, $feature);
        $this->feature = $feature;
        $this->feature->register($this->eventManager);
    }

    public function testRegisterRegistersBothListeners(): void
    {
        $consoleInitListeners = $this->eventManager->getListeners('CONSOLE_INIT');
        $this->assertNotEmpty($consoleInitListeners);

        $postLoopListeners = $this->eventManager->getListeners('POST_LOOP');
        $this->assertNotEmpty($postLoopListeners);
    }

    public function testRegisterShortcodeRegistersWhenManagerAvailable(): void
    {
        $shortcodeManager = $this->createMock(ShortcodeManager::class);
        $shortcodeManager->expects($this->once())->method('register');
        $this->container->add(ShortcodeManager::class, $shortcodeManager);

        $event = new ConsoleInitEvent('CONSOLE_INIT', new Application());
        $this->feature->registerShortcode($event);
    }

    public function testRegisterShortcodeWarnsWhenManagerMissing(): void
    {
        $this->logger->expects($this->once())
            ->method('log')
            ->with('WARNING', $this->stringContains('ShortcodeManager not found'));

        $event = new ConsoleInitEvent('CONSOLE_INIT', new Application());
        $this->feature->registerShortcode($event);
    }

    public function testCopyAssetsDoesNothingWithoutOutputDir(): void
    {
        // No OUTPUT_DIR set — should return without attempting a copy
        $this->logger->expects($this->never())->method('log');

        $this->feature->copyAssets(new Event('POST_LOOP'));
    }

    public function testCopyAssetsCopiesVendorAssetsToOutputDir(): void
    {
        $outputDir = sys_get_temp_dir() . '/staticforge_gallery_test_' . uniqid();
        mkdir($outputDir, 0755, true);
        $this->setContainerVariable('OUTPUT_DIR', $outputDir);

        try {
            $this->feature->copyAssets(new Event('POST_LOOP'));

            // The Feature's own bundled vendor assets should have been copied
            $this->assertDirectoryExists($outputDir . '/assets/vendor/gallery');
        } finally {
            $this->removeDirectory($outputDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
