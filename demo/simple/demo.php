<?php

/**
 * Simple demostration on how to optimize a few images.
 * ----------------------------------------------------
 * Optimizations overwrites the images, to prevent that so the demo can be run multiple times, they're copied to new
 * files before being processed.
 * It requires jpegoptim & optipng to be installed on your system, otherwise the commands will fail.
 */

ini_set("display_errors",true);
error_reporting(E_ALL);

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

// 3. Optimize images
    $images = array("sample.jpg","sample.png","sample.gif","sample.txt","missingfile.png");
    foreach($images as $file) {
        $optimizedFile="optimized_".$file;
        if (file_exists($file)) {
            // OPTIONAL: Copy image to work on a copy and leave the original untouched. Optimizations are usually performed directly on the same file.
            copy($file, $optimizedFile);
        }
        echo PHP_EOL."Optimizing ".$optimizedFile."... ";
        $result = $imageOptim->optimizeImage($optimizedFile);
        if ($result->success) {
            printf("SUCCESS (%.2f%% optimized)",$result->percentOptimized);
        } elseif ($result->ignored) {
            echo "IGNORED (".$result->exception->getMessage().")";
        } else {
            echo "FAILED (".$result->exception->getMessage().")";
        }
    }
    echo PHP_EOL."DONE".PHP_EOL;