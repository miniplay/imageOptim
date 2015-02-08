imageOptim
==========

PHP 5.3+ wrapper to perform image optimizations supporting any command line tool.

Features
--------
* Supports any image optimization command line tool: jpegoptim, optipng, pngcrush, gifsicle, jpegtran...
* Personalizable arguments per command line optimizer: tune each optimizer as you like.
* Supports multiple optimizers for the same image type.
* Ovewrites images only if they can be optimized (depending on the optimizer but they all work the same).
* Can process single images or complete directories (recursively).
* When processing directories recursively a callback can be provided to be notified when each file is processed to measure the progress.

Limitations
-----------
* It executes system commands and it's not restricted to image optimization commands, any system command can be run. Proceed with caution.
* Optimization commands availability is not checked upon optimizer registration, commands will fail and image will be left unoptimized.

Roadmap
-------
* Add tests & travis build

How to use
----------

1. Install some image optimizers on your system:
```
$ yum install jpegoptim
$ yum install optipng
...
```

2. Add dependency to your composer.json and run composer update
```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/miniplay/imageOptim"
        }
    ],
    "require": {
        "miniplay/imageOptim": "1.0.0"
    }
}
```

3. Include the following code in your PHP App:
```
$imageOptim = new ImageOptim\ImageOptim();

// Register JPEG optimizer command
    $jpegOptimizer = new ImageOptim\Optimizer("jpegoptim", array(IMAGETYPE_JPEG));
    $jpegOptimizer->addPrevArgument("--strip-all");
    $imageOptim->registerOptimizer($jpegOptimizer);

// Register PNG optimizer command
    $pngOptimizer = new ImageOptim\Optimizer("optipng", array(IMAGETYPE_PNG));
    $pngOptimizer->addPrevArgument("-o3");
    $imageOptim->registerOptimizer($pngOptimizer);
    
// Optimize image
    $result = $imageOptim->optimizeImage("sample.png"); // $result is a ImageOptim\Result\FileResult full of public properties :)
    
// Print result
    if ($result->success) {
        printf("SUCCESS (%.2f%% optimized)",$result->percentOptimized);
    } elseif ($result->ignored) {
        echo "IGNORED (".$result->exception->getMessage().")";
    } else {
        echo "FAILED (".$result->exception->getMessage().")";
    }
```

Check demo/simple/demo.php & demo/tree/demp.php for full working examples