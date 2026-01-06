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
        $limit = isset($attributes['limit']) ? (int)$attributes['limit'] : 20;

        // Split images for pagination
        $initialImages = array_slice($images, 0, $limit);
        $remainingImages = array_slice($images, $limit);

        // Use AssetManager to queue scripts and styles
        if ($this->container && $this->container->has(AssetManager::class)) {
            $assetManager = $this->container->get(AssetManager::class);

            // Styles
            $assetManager->addStyle('justifiedGallery', 'https://cdnjs.cloudflare.com/ajax/libs/justifiedGallery/3.8.1/css/justifiedGallery.min.css');
            $assetManager->addStyle('magnificPopup', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css');

            // Scripts - Loaded in HEADER (false) because inline scripts depend on them immediately
            $assetManager->addScript('jquery', 'https://code.jquery.com/jquery-3.7.1.min.js', [], false);
            $assetManager->addScript('justifiedGallery', 'https://cdnjs.cloudflare.com/ajax/libs/justifiedGallery/3.8.1/js/jquery.justifiedGallery.min.js', ['jquery'], false);
            $assetManager->addScript('magnificPopup', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js', ['jquery'], false);
        }

        $html = '';

        $html .= '<div class="justified-gallery" id="' . $id . '">';
        foreach ($initialImages as $image) {
            $html .= sprintf(
                '<a href="%s" title="%s"><img src="%s" alt="%s" /></a>',
                htmlspecialchars($image['src']),
                htmlspecialchars($image['title']),
                htmlspecialchars($image['thumb']),
                htmlspecialchars($image['alt'])
            );
        }
        $html .= '</div>';

        if (!empty($remainingImages)) {
            $btnId = $id . '-more';
            $html .= '<div style="text-align: center; margin-top: 20px; margin-bottom: 20px;">';
            $html .= '<button id="' . $btnId . '" class="btn btn-primary" style="padding: 10px 20px; cursor: pointer;">Load More</button>';
            $html .= '</div>';

            // Pass remaining images to JS
            $jsVarName = 'galleryImages_' . str_replace('-', '_', $id);
            $limitVarName = 'galleryLimit_' . str_replace('-', '_', $id);

            $html .= "\n<script>\n";
            $html .= 'var ' . $jsVarName . ' = ' . json_encode($remainingImages) . ';' . "\n";
            $html .= 'var ' . $limitVarName . ' = ' . $limit . ';' . "\n";
            $html .= "</script>\n";
        }

        // Add initialization script
        $html .= "\n<script>\n";
        $html .= 'jQuery(document).ready(function($) {' . "\n";
        $html .= 'var $gallery = $("#' . $id . '");' . "\n";
        $html .= '$gallery.justifiedGallery({' . "\n";
        $html .= 'rowHeight: ' . $rowHeight . ',' . "\n";
        $html .= 'margins: ' . $margins . ',' . "\n";
        $html .= 'lastRow: "' . htmlspecialchars($lastRow) . '"' . "\n";
        $html .= '}).on("jg.complete", function () {' . "\n";
        $html .= '$(this).magnificPopup({' . "\n";
        $html .= 'delegate: "a",' . "\n";
        $html .= 'type: "image",' . "\n";
        $html .= 'gallery: {' . "\n";
        $html .= 'enabled: true' . "\n";
        $html .= '}' . "\n";
        $html .= '});' . "\n";
        $html .= '});' . "\n";

        if (!empty($remainingImages)) {
             $html .= '$("#' . $btnId . '").on("click", function() {' . "\n";
             $html .= 'var images = ' . $jsVarName . ';' . "\n";
             $html .= 'var limit = ' . $limitVarName . ';' . "\n";
             $html .= 'var nextBatch = images.splice(0, limit);' . "\n";
             $html .= 'var html = "";' . "\n";
             $html .= '$.each(nextBatch, function(index, image) {' . "\n";
             $html .= 'html += \'<a href="\' + image.src + \'" title="\' + image.title + \'"><img src="\' + image.thumb + \'" alt="\' + image.alt + \'" /></a>\';' . "\n";
             $html .= '});' . "\n";
             $html .= '$gallery.append(html);' . "\n";
             $html .= '$gallery.justifiedGallery("norewind");' . "\n";
             $html .= 'if (images.length === 0) {' . "\n";
             $html .= '$(this).hide();' . "\n";
             $html .= '}' . "\n";
             $html .= '});' . "\n";
        }

        $html .= '});' . "\n";
        $html .= "</script>\n";

        return $html;
    }
}
