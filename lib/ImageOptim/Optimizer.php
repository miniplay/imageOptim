<?php

namespace ImageOptim;

use ImageOptim\Result\FileResult;
use ImageOptim\Result\OptimizationResult;
use ImageOptim\Exception;
use ImageOptim\Exception\ExceptionExec;
use ImageOptim\Exception\ExceptionInvalidCommand;

class Optimizer {

    const ARGUMENT_PREV = "PREV";
    const ARGUMENT_POST = "POST";

    const VAR_TARGET_PATH = "{TARGET_PATH}";    // Argument variable: full target path (/tmp/logo.jpg)
    const VAR_TARGET_FILE = "{TARGET_FILE}";    // Argument variable: filename (logo.jpg)
    const VAR_TARGET_NAME = "{TARGET_NAME}";    // Argument variable: name (logo)
    const VAR_TARGET_DIR = "{TARGET_DIR}";      // Argument variable: target dir (/tmp/)

    /**
     * Exif image types supported, check IMAGETYPE_XXXXX constants
     * @var array
     */
    protected $exifImageTypes = array();

    /**
     * @var
     */
    protected $command;

    /**
     * Prev arguments
     * @var array
     */
    protected $prev_arguments = array();

    /**
     * Post arguments
     * @var array
     */
    protected $post_arguments = array();

    /**
     * @param string $command
     * @param array $exifImageTypes
     * @param bool|true $autoAddTargetPathAsArgument automatically add target filename as first of the post arguments
     * @throws ExceptionInvalidCommand
     */
    public function __construct($command, array $exifImageTypes, $autoAddTargetPathAsArgument = true) {
        if (strlen($command)<2) {
            throw new ExceptionInvalidCommand("Invalid optimization command: ".$command);
        }
        $this->command = $command;
        $this->exifImageTypes = $exifImageTypes;
        if ($autoAddTargetPathAsArgument) {
            $this->addPostArgument(self::VAR_TARGET_PATH);
        }
    }

    /**
     * Optimizes the image
     * @param FileResult $result
     * @return OptimizationResult|null
     */
    public function optimize(FileResult $result)  {
        if (!in_array($result->exifImageType, $this->exifImageTypes)) {
            return null; /* Ignore optimizer for this file */
        }
        $optimResult = new OptimizationResult($this);
        $optimResult->originalSize = $result->fileInfo->getSize();
        try {
            $arguments = implode(" ",$this->prev_arguments) . " ".implode(" ",$this->post_arguments);
            $arguments = str_replace(array(
                self::VAR_TARGET_PATH,
                self::VAR_TARGET_FILE,
                self::VAR_TARGET_NAME,
                self::VAR_TARGET_DIR
            ),array(
                $result->filePath,
                $result->fileInfo->getFilename(),
                $result->fileInfo->getBasename($result->fileInfo->getExtension()),
                dirname($result->filePath)
            ),$arguments);
            $optimResult->optimizerCommand = $this->command . " " . $arguments . " 2>&1";
            $optimResult->optimizerOutput = shell_exec($optimResult->optimizerCommand);
            if ($optimResult->optimizerOutput===null) {
                throw new ExceptionExec("Optimization command returned null: ".$optimResult->optimizerCommand);
            }
        } catch(\Exception $e) {
            $optimResult->exception = new ExceptionExec("Error executing optimization command: ".$e->getMessage(), 0, $e);
        }
        clearstatcache(true, $result->filePath); // Refresh cache for the file to get the new file size
        $optimResult->optimizedSize = $result->fileInfo->getSize();
        $optimResult->bytesOptimized = $optimResult->originalSize - $optimResult->optimizedSize;
        if ($optimResult->originalSize > 0) {
            $optimResult->percentOptimized = round(($optimResult->bytesOptimized / $optimResult->originalSize) * 100, 2);
        }
        if (!$optimResult->exception) {
            $optimResult->success = true;
        }
        // Append optimization operation automatically
        $result->optimizations[] = $optimResult;
        if ($optimResult->exception) {
            $result->exception = $optimResult->exception;
        }
        return $optimResult;
    }

    /**
     * Adds the argument as prev argument (before post arguments)
     * @param string $argument argument to add (variables available at self::VAR_XXXXXXX)
     */
    public function addPrevArgument($argument) {
        $this->addArgument(self::ARGUMENT_PREV, $argument);
    }

    /**
     * Adds the argument as post argument (after prev arguments)
     * @param string $argument argument to add (variables available at self::VAR_XXXXXXX)
     */
    public function addPostArgument($argument) {
        $this->addArgument(self::ARGUMENT_POST, $argument);
    }

    /**
     * Adds an argument
     * @param string $position
     * @param string $argument
     * @throws Exception
     * @throws InvalidArgumentException
     */
    protected function addArgument($position, $argument) {
        if (empty($argument) || !is_string($argument)) {
            throw new InvalidArgumentException('$argument must be a string');
        }
        switch($position) {
            case self::ARGUMENT_POST:
                $this->post_arguments[] = $argument;
                break;
            case self::ARGUMENT_PREV:
                $this->prev_arguments[] = $argument;
                break;
            default:
                throw new Exception('Unsupported argument position: '.$position);
                break;
        }
    }


}
