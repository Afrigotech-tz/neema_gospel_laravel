<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;

class ImageProcessingService
{
    protected $config;
    protected $manager;

    public function __construct()
    {
        $this->config = config('image_upload');
        $this->manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
    }

    /**
     * Process image with quality settings
     */
    public function processImage(string $path, string $type = 'default', array $customOptions = []): array
    {
        $options = $this->getOptionsForType($type);
        $options = array_merge($options, $customOptions);

        try {
            $fullPath = Storage::disk('public')->path($path);
            $image = $this->manager->read($fullPath);

            // Backup original if requested
            if ($options['backup_original'] ?? $this->config['backup']['enabled']) {
                $this->backupOriginal($path);
            }

            // Get original dimensions
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Resize if needed
            if ($options['resize'] && ($originalWidth > $options['max_width'] || $originalHeight > $options['max_height'])) {
                $image->scaleDown($options['max_width'], $options['max_height']);
            }

            // Save with quality settings
            $quality = $options['quality'] ?? $this->config['quality'][$type] ?? 95;
            $image->save($fullPath, $quality);

            return [
                'success' => true,
                'original_dimensions' => [$originalWidth, $originalHeight],
                'processed_dimensions' => [$image->width(), $image->height()],
                'quality' => $quality,
                'file_size' => filesize($fullPath),
            ];

        } catch (\Exception $e) {
            Log::error('Image processing failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get processing options for specific type
     */
    protected function getOptionsForType(string $type): array
    {
        $dimensions = $this->config['dimensions'][$type] ?? $this->config['dimensions']['default'] ?? [
            'max_width' => 1920,
            'max_height' => 1080,
            'resize' => false,
        ];

        $quality = $this->config['quality'][$type] ?? 95;

        return [
            'quality' => $quality,
            'max_width' => $dimensions['max_width'],
            'max_height' => $dimensions['max_height'],
            'resize' => $dimensions['resize'],
            'backup_original' => $this->config['backup']['enabled'],
        ];
    }

    /**
     * Create backup of original image
     */
    protected function backupOriginal(string $path): void
    {
        try {
            $fullPath = Storage::disk('public')->path($path);
            $backupDir = dirname($path) . '/' . $this->config['backup']['backup_directory'];

            // Ensure backup directory exists
            $backupPath = $backupDir . '/' . basename($path);
            $backupFullPath = Storage::disk('public')->path($backupPath);

            if (!is_dir(dirname($backupFullPath))) {
                mkdir(dirname($backupFullPath), 0755, true);
            }

            // Copy original to backup location
            if (!file_exists($backupFullPath)) {
                copy($fullPath, $backupFullPath);
            }

        } catch (\Exception $e) {
            Log::warning('Failed to create image backup: ' . $e->getMessage());
        }
    }

    /**
     * Get image information
     */
    public function getImageInfo(string $path): array
    {
        try {
            $fullPath = Storage::disk('public')->path($path);
            if (!file_exists($fullPath)) {
                return ['exists' => false];
            }

            $image = $this->manager->read($fullPath);

            return [
                'exists' => true,
                'width' => $image->width(),
                'height' => $image->height(),
                'file_size' => filesize($fullPath), // Use filesize() for file size
                'mime_type' => mime_content_type($fullPath), // Use mime_content_type() for MIME type
            ];

        } catch (\Exception $e) {
            return [
                'exists' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert image to WebP format
     */
    public function convertToWebP(string $path): string
    {
        try {
            $fullPath = Storage::disk('public')->path($path);
            $image = $this->manager->read($fullPath);

            $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $fullPath);
            $webpStoragePath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $path);

            $image->save($webpPath, $this->config['compression']['webp_quality'] ?? 95);

            // Remove original if WebP conversion successful
            if (file_exists($webpPath)) {
                unlink($fullPath);
                return $webpStoragePath;
            }

            return $path;

        } catch (\Exception $e) {
            Log::warning('WebP conversion failed: ' . $e->getMessage());
            return $path;
        }
    }
}
