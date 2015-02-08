<?php

namespace ImageOptim;

use DirectoryIterator;
use ImageOptim\Exception\Exception;
use ImageOptim\Exception\ExceptionInvalidDir;
use ImageOptim\Result\FileResult;
use ImageOptim\Exception\ExceptionInvalidImage;
use ImageOptim\Exception\ExceptionNoOptimizer;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ImageOptim {

    /**
     * @var Optimizer[]
     */
    protected $optimizers = array();

    /**
     * @param Optimizer $optimizer
     * @param int $exifImageType Check IMAGETYPE_XXXXX constants
     */
    public function registerOptimizer(Optimizer $optimizer) {
        $this->optimizers[] = $optimizer;
    }

    /**
     * @param string $imagePath
     * @return FileResult
     */
    public function optimizeImage($imagePath) {
        $result = new FileResult($imagePath);
        $result->success=false;
        if (!$result->exception) {
            $result->originalSize = $result->fileInfo->getSize();
            $result->exifImageType = @exif_imagetype($imagePath); // exif_imagetype gives a notice if the file it's empty
            if ($result->exifImageType) {
                $result->ignored = false;
                $result->exifImageTypeExtension = image_type_to_extension($result->exifImageType, false);
                try {
                    foreach ($this->optimizers as $optimizer) {
                        $optimResult = $optimizer->optimize($result);
                        if ($optimResult !== null && $optimResult->exception) {
                            break; // Stop further processing
                        }
                    }
                    if (count($result->optimizations) == 0) {
                        $result->exception = new ExceptionNoOptimizer("No optimizers registered for " . $result->exifImageTypeExtension . " images");
                    }
                    if (!$result->exception) {
                        $result->success = true;
                    }
                } catch (\Exception $e) {
                    $result->exception = $e;
                }
                clearstatcache(true, $result->filePath); // Refresh cache for the file to get the new file size
                $result->optimizedSize = $result->fileInfo->getSize();
                $result->bytesOptimized = $result->originalSize - $result->optimizedSize;
                if ($result->originalSize > 0) {
                    $result->percentOptimized = round(($result->bytesOptimized / $result->originalSize) * 100, 2);
                }
            } else {
                $result->ignored = true;
                $result->exception = new ExceptionInvalidImage("Not a valid image");
            }
        }
        if (!$result->success && $result->exception===null) {
            $result->exception = new Exception("Operation failed");
        }
        return $result;
    }

    /**
     * Performs registered optimizations on all files of a directory (Recursive)
     * @param string $imagesDir
     * @param callable|null $onImageProcessedCallback Callback called after every image processed
     * @throws ExceptionInvalidDir
     * @return FileResult[]
     */
    public function optimizeDirRecursive($imagesDir, Callable $onImageProcessedCallback = null ) {
        return $this->optimizeDir($imagesDir, $onImageProcessedCallback, true);
    }

    /**
     * Performs registered optimizations on all files of a directory
     * @param string $imagesDir
     * @param bool|false $recursive
     * @param callable|null $onImageProcessedCallback Callback called after every image processed
     * @throws ExceptionInvalidDir
     * @return FileResult[]
     */
    public function optimizeDir($imagesDir, Callable $onImageProcessedCallback = null, $recursive = false) {
        $imagesDir = realpath($imagesDir);
        $dirInfo = new SplFileInfo($imagesDir);
        if (!$dirInfo->isReadable() || !$dirInfo->isDir()) {
            throw new ExceptionInvalidDir("Path " . $imagesDir . " doesn't exists, cannot be read or it's not a directory");
        } elseif(!$dirInfo->isWritable()) {
            throw new ExceptionInvalidDir("Path " . $imagesDir . " is not writable");
        }
        // Read and process files
        $directory = $recursive ? new RecursiveDirectoryIterator($imagesDir) : new DirectoryIterator($imagesDir);
        $iterator = new RecursiveIteratorIterator($directory);
        $results = array();
        // Get files count
        $totalFiles = 0;
        foreach ($iterator as $key => $file /* @var $file SplFileInfo */) {
            if ($file->isFile()) {
                $totalFiles++;
            }
        }
        $processedFiles = 0;
        foreach ($iterator as $key => $file /* @var $file SplFileInfo */) {
            if ($file->isFile()) {
                ++$processedFiles;
                $result = $this->optimizeImage($file->getRealPath());
                $results[] = $result;
                if ($onImageProcessedCallback) {
                    $onImageProcessedCallback($result, $processedFiles, $totalFiles);
                }
            }
        }
        return $results;
    }

}