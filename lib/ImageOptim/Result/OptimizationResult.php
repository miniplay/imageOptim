<?php
namespace ImageOptim\Result;

use ImageOptim\Optimizer;

class OptimizationResult {

    /**
     * @var Optimizer
     */
    public $optimizer;
    /**
     * @var string
     */
    public $optimizerCommand = "";
    /**
     * @var string
     */
    public $optimizerOutput = "";
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
     * @var bool
     */
    public $success = false;
    /**
     * @var \Exception
     */
    public $exception = null;

    public function __construct(Optimizer $optimizer) {
        $this->optimizer = $optimizer;
    }

}