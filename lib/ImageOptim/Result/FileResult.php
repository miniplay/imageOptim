<?php

namespace ImageOptim\Result;

use ImageOptim\Exception\ExceptionInvalidFile;

class FileResult {

    /**
     * File information object
     * @var \SplFileInfo
     */
    public $fileInfo;
    /**
     * Full image path: /tmp/logo.jpg
     * @var string
     */
    public $filePath;
    /**
     * Performed optimizations
     * @var OptimizationResult[]
     */
    public $optimizations = array();
    /**
     * @var int
     */
    public $exifImageType = false;
    /**
     * @var string
     */
    public $exifImageTypeExtension = "";
    /**
     * @var int
     */
    public $originalSize = 0;
    /**
     * @var int
     */
    public $optimizedSize = 0;
    /**
     * @var int
     */
    public $bytesOptimized = 0;
    /**
     * @var float
     */
    public $percentOptimized = 0.0;
    /**
     * Was optimized successfully?
     * @var bool
     */
    public $success;
    /**
     * Was the file ignored? (because not being an image)
     * @var bool
     */
    public $ignored;
    /**
     * Optimizer exception
     * @var \Exception
     */
    public $exception = null;

    public function __construct($filePath) {
        $this->filePath = $filePath;
        $this->fileInfo = new \SplFileInfo($this->filePath);
        if (!$this->fileInfo->isReadable() || !$this->fileInfo->isFile()) {
            $this->exception = new ExceptionInvalidFile("Path " . $filePath . " doesn't exists, it cannot be read or it's not a file");
        } elseif (!$this->fileInfo->isWritable()) {
            $this->exception = new ExceptionInvalidFile("Path " . $filePath . " is not writable");
        }
        $this->filePath = $this->fileInfo->getRealPath(); // Use realpath
    }

    /**
     * Is the file an image?
     * @return bool
     */
    public function isImage() {
        return $this->exifImageType > 0;
    }


}