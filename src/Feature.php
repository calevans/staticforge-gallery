<?php

declare(strict_types=1);

namespace Calevans\Gallery;

use EICC\StaticForge\Core\BaseFeature;
use EICC\StaticForge\Core\FeatureInterface;
use EICC\StaticForge\Core\EventManager;
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

    /**
     * @var array<string, array{method: string, priority: int}>
     */
    protected array $eventListeners = [
        'POST_LOOP' => ['method' => 'copyAssets', 'priority' => 100]
    ];

    public function register(EventManager $eventManager, Container $container): void
    {
        parent::register($eventManager, $container);
        $this->logger = $container->get('logger');
        $this->galleryService = new GalleryService($this->logger);

        // Register Shortcode
        // We check if ShortcodeManager is available.
        // Note: ShortcodeProcessor must be loaded before this feature.
        try {
            $shortcodeManager = $container->get(ShortcodeManager::class);
            if ($shortcodeManager) {
                $shortcode = new GalleryShortcode($this->galleryService);
                $shortcodeManager->register($shortcode);
                $this->logger->log('INFO', 'Gallery shortcode registered.');
            } else {
                $this->logger->log('WARNING', 'ShortcodeManager not found in container. Gallery shortcode not registered.');
            }
        } catch (\Exception $e) {
             $this->logger->log('WARNING', 'Failed to register Gallery shortcode: ' . $e->getMessage());
        }
    }

    public function copyAssets(Container $container): void
    {
        $outputDir = $container->getVariable('OUTPUT_DIR');
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
