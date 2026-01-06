<?php

declare(strict_types=1);

namespace Calevans\Gallery\Services;

use EICC\Utils\Log;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use SplFileInfo;

class GalleryService
{
    private Log $logger;

    public function __construct(Log $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get images from a gallery directory
     *
     * @param string $baseDir The base content directory
     * @param string $galleryPath The relative path to the gallery inside assets/images
     * @return array<int, array<string, string>>
     */
    public function getImages(string $baseDir, string $galleryPath): array
    {
        // Clean up the gallery path
        $cleanPath = trim($galleryPath, '/');

        // Construct full path: content/{galleryPath}
        // We assume the user provides the full relative path from source root, e.g. "assets/images/trip"
        $fullPath = rtrim($baseDir, '/') . DIRECTORY_SEPARATOR . $cleanPath;

        if (!is_dir($fullPath)) {
            $this->logger->log('WARNING', "Gallery path not found: {$fullPath}");
            return [];
        }

        $images = [];
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile() && $this->isImage($file)) {
                    // Calculate relative path for the src attribute
                    // It should be /{galleryPath}/{filename}
                    // We use rawurlencode to handle spaces and special characters in filenames
                    $relativePath = '/' . $cleanPath . '/' . rawurlencode($file->getFilename());

                    $images[] = [
                        'src' => $relativePath,
                        'thumb' => $relativePath, // Using same image for now
                        'alt' => $this->formatTitle($file->getBasename('.' . $file->getExtension())),
                        'title' => $this->formatTitle($file->getBasename('.' . $file->getExtension())),
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->logger->log('ERROR', "Error scanning gallery directory: " . $e->getMessage());
            return [];
        }

        // Sort images by filename
        usort($images, fn($a, $b) => strcmp($a['src'], $b['src']));

        return $images;
    }

    private function isImage(SplFileInfo $file): bool
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        return in_array(strtolower($file->getExtension()), $extensions);
    }

    private function formatTitle(string $filename): string
    {
        // Replace hyphens and underscores with spaces and capitalize
        return ucwords(str_replace(['-', '_'], ' ', $filename));
    }
}
