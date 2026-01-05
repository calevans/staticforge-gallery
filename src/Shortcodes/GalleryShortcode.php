<?php

declare(strict_types=1);

namespace Calevans\Gallery\Shortcodes;

use EICC\StaticForge\Shortcodes\BaseShortcode;
use Calevans\Gallery\Services\GalleryService;
use EICC\StaticForge\Core\AssetManager;

class GalleryShortcode extends BaseShortcode
{
    private GalleryService $galleryService;

    public function __construct(GalleryService $galleryService)
    {
        $this->galleryService = $galleryService;
    }

    public function getName(): string
    {
        return 'gallery';
    }

    public function handle(array $attributes, string $content = ''): string
    {
        $path = $attributes['path'] ?? '';
        if (empty($path)) {
            return '<div class="alert alert-danger">Gallery path not specified</div>';
        }

        $sourceDir = $this->container->getVariable('SOURCE_DIR');
        if (!$sourceDir) {
             return '<div class="alert alert-danger">Source directory not configured</div>';
        }

        // Check if path exists relative to source root, if not, try assets/images/ (Legacy support)
        $fullPath = rtrim($sourceDir, '/') . '/' . trim($path, '/');
        if (!is_dir($fullPath)) {
            $legacyPath = 'assets/images/' . trim($path, '/');
            $legacyFullPath = rtrim($sourceDir, '/') . '/' . $legacyPath;
            if (is_dir($legacyFullPath)) {
                $path = $legacyPath;
            }
        }

        $images = $this->galleryService->getImages($sourceDir, $path);

        if (empty($images)) {
            return '<div class="alert alert-warning">No images found in gallery: ' . htmlspecialchars($path) . '</div>';
        }

        $id = 'gallery-' . uniqid();
        
        // Parse options
        $rowHeight = isset($attributes['rowHeight']) ? (int)$attributes['rowHeight'] : 200;
        $margins = isset($attributes['margins']) ? (int)$attributes['margins'] : 10;
        $lastRow = $attributes['lastRow'] ?? 'nojustify';

        // Register assets via AssetManager
        try {
            $assetManager = $this->container->get(AssetManager::class);
            
            // Styles
            $assetManager->addStyle('justified-gallery', '/assets/vendor/gallery/css/justifiedGallery.min.css');
            $assetManager->addStyle('magnific-popup', '/assets/vendor/gallery/css/magnific-popup.min.css');
            
            // Scripts
            $assetManager->addScript('jquery', '/assets/vendor/gallery/js/jquery.min.js');
            $assetManager->addScript('justified-gallery', '/assets/vendor/gallery/js/jquery.justifiedGallery.min.js', ['jquery']);
            $assetManager->addScript('magnific-popup', '/assets/vendor/gallery/js/jquery.magnific-popup.min.js', ['jquery']);
            
        } catch (\Exception $e) {
            // Fallback if AssetManager is not available (should not happen in normal flow)
        }

        $html = '<div class="justified-gallery" id="' . $id . '">';
        foreach ($images as $image) {
            $html .= sprintf(
                '<a href="%s" title="%s"><img src="%s" alt="%s" /></a>',
                htmlspecialchars($image['src']),
                htmlspecialchars($image['title']),
                htmlspecialchars($image['thumb']),
                htmlspecialchars($image['alt'])
            );
        }
        $html .= '</div>';

        // Add initialization script
        // We can't easily add inline scripts via AssetManager yet, so we keep this here for now.
        // Ideally, AssetManager would support inline scripts too.
        $html .= '<script>
            jQuery(document).ready(function($) {
                $("#' . $id . '").justifiedGallery({
                    rowHeight: ' . $rowHeight . ',
                    margins: ' . $margins . ',
                    lastRow: "' . htmlspecialchars($lastRow) . '"
                }).on("jg.complete", function () {
                    $(this).magnificPopup({
                        delegate: "a",
                        type: "image",
                        gallery: {
                            enabled: true
                        }
                    });
                });
            });
        </script>';

        return $html;
    }
}
