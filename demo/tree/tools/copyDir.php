<?php

/**
 * Helper method just for creating a copy of the images directory
 * @param $sourceDir
 * @param $destDir
 * @param int $permissions dest directory permissions, defaults to 0777
 * @return array of copied files
 * @throws Exception
 */
function copyDir($sourceDir, $destDir, $permissions = 0777) {
    $sourceDir = realpath(rtrim($sourceDir, DIRECTORY_SEPARATOR));
    if (!$sourceDir || !is_dir($sourceDir)) {
        throw new \Exception("Source directory ".$sourceDir." does not exist or is not a directory");
    }
    if (strlen(trim($destDir,DIRECTORY_SEPARATOR))==0) {
        throw new \Exception("Invalid or empty destination directory: ".$destDir);
    }
    $destDir = rtrim($destDir, DIRECTORY_SEPARATOR);
    $directory = new RecursiveDirectoryIterator($sourceDir);
    $destFiles = array();
    foreach (new RecursiveIteratorIterator($directory) as $filename => $current) {
        /* @var SplFileInfo $current */
        $currentSource = $current->getRealPath();
        $currentDest = $destDir . substr($currentSource, strlen($sourceDir));
        if ($current->isDir()) {
            if (!file_exists($currentDest)) {
                mkdir($currentDest, $permissions, true);
            }
        } elseif($current->isFile()) {
            $destFiles[] = $currentDest;
            copy($currentSource, $currentDest);
        }
    }
    return $destFiles;
}