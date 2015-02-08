<?php

/**
 * Simple demostration on how to recursively optimize a directory.
 * --------------------------------------------------------------
 * To make it shorter, no validation checks on source an destination dirs are performed.
 * copyDir() is defined in tools/copyDir.php to recursively copy directories (not required to use the library)
 * It requires jpegoptim & optipng to be installed on your system, otherwise the commands will fail.
 */

ini_set("display_errors",true);
error_reporting(E_ALL);

require("./tools/copyDir.php");
require("../../vendor/autoload.php");

Header("Content-type: text/plain");

// 1. Instantiate ImageOptim
    $imageOptim = new ImageOptim\ImageOptim();

// 2.a. Register JPEG optimizer command (jpegoptim must be installed in your system: yum install jpegoptim)
    $jpegOptimizer = new ImageOptim\Optimizer("jpegoptim", array(IMAGETYPE_JPEG));
    $jpegOptimizer->addPrevArgument("--strip-all"); // Prepend argument (before the filename)
    $imageOptim->registerOptimizer($jpegOptimizer);

// 2.b. Register PNG optimizer command (optipng must be installed in your system: yum install optipng)
    $pngOptimizer = new ImageOptim\Optimizer("optipng", array(IMAGETYPE_PNG));
    $pngOptimizer->addPrevArgument("-o3"); // Prepend argument (before the filename)
    $imageOptim->registerOptimizer($pngOptimizer);

// 2.c. No GIF optimizer registered to test the "No optimizers registered for gif images" exception

// 3. OPTIONAL: Copy images to work on a copy and leave the originals untouched. Optimizations are usually performed directly on the same files.
    $dir = "images";
    $optimizedDir = "optimized_".$dir;
    if (file_exists($dir) && is_dir($dir)) {
        // Simple method to recursively copy a directory, not part of the library.
        copyDir($dir, $optimizedDir);
    }

// 4. Optimize directory
    echo PHP_EOL."Optimizing directory ".$optimizedDir."... ";
    // OPTIONAL: Define a callback to receive intermediate reports on each processed file
    $onImageProcessedCallback = function(\ImageOptim\Result\FileResult $currentResult, $numProcessedFiles, $numTotalFiles) {
        echo PHP_EOL."  ".$numProcessedFiles . "/" . $numTotalFiles.": ".$currentResult->filePath . " processed " . ($currentResult->success ? "OK":"KO") . ($currentResult->ignored ? " (IGNORED)":"");
    };
    $results = $imageOptim->optimizeDirRecursive($optimizedDir, $onImageProcessedCallback);
    echo PHP_EOL.PHP_EOL."RESULTS ---------";
    foreach($results as $result) {
        echo PHP_EOL."  File ".$result->filePath.": ";
        if ($result->success) {
            printf("SUCCESS (%.2f%% optimized)",$result->percentOptimized);
        } elseif ($result->ignored) {
            echo "IGNORED (".$result->exception->getMessage().")";
        } else {
            echo "FAILED (".$result->exception->getMessage().")";
        }
    }

echo PHP_EOL."DONE".PHP_EOL;