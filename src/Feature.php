<?php

declare(strict_types=1);

namespace Calevans\Gallery;

use EICC\StaticForge\Core\BaseFeature;
use EICC\StaticForge\Core\Events\ConsoleInitEvent;
use EICC\StaticForge\Core\Events\Event;
use EICC\StaticForge\Core\Events\EventListener;
use EICC\StaticForge\Core\FeatureInterface;
use Calevans\Gallery\Services\GalleryService;
use Calevans\Gallery\Shortcodes\GalleryShortcode;
use EICC\StaticForge\Shortcodes\ShortcodeManager;
use EICC\Utils\Container;
use EICC\Utils\Log;

class Feature extends BaseFeature implements FeatureInterface
{
    protected string $name = 'PhotoGallery';
    protected Log $logger;
    private GalleryService $galleryService;

    public function __construct(Container $container, Log $logger, GalleryService $galleryService)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->galleryService = $galleryService;
    }

    /**
     * Register the gallery shortcode.
     *
     * Runs on CONSOLE_INIT (not register()) because ShortcodeManager,
     * loaded by the ShortcodeProcessor feature, is not guaranteed to be
     * registered in the container yet at Feature::register() time.
     */
    #[EventListener('CONSOLE_INIT', priority: 0)]
    public function registerShortcode(ConsoleInitEvent $event): void
    {
        try {
            if ($this->container->has(ShortcodeManager::class)) {
                $shortcodeManager = $this->container->get(ShortcodeManager::class);
                $shortcode = new GalleryShortcode($this->galleryService);
                $shortcodeManager->register($shortcode);
                $this->logger->log('INFO', 'Gallery shortcode registered.');
            } else {
                $this->logger->log(
                    'WARNING',
                    'ShortcodeManager not found in container. Gallery shortcode not registered.'
                );
            }
        } catch (\Exception $e) {
            $this->logger->log('WARNING', 'Failed to register Gallery shortcode: ' . $e->getMessage());
        }
    }

    #[EventListener('POST_LOOP', priority: 100)]
    public function copyAssets(Event $event): void
    {
        $outputDir = $this->container->getVariable('OUTPUT_DIR');
        if (!$outputDir) {
            return;
        }

        $sourceDir = __DIR__ . '/assets/vendor';
        $targetDir = $outputDir . '/assets/vendor/gallery';

        if (is_dir($sourceDir)) {
            $this->logger->log('INFO', "Copying gallery assets to {$targetDir}");
            $this->copyDirectory($sourceDir, $targetDir);
        }
    }

    private function copyDirectory(string $source, string $dest): bool
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
        return true;
    }
}
