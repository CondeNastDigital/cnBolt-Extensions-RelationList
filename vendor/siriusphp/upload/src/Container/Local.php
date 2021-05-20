<?php

namespace Sirius\Upload\Container;

class Local implements ContainerInterface
{

    protected $baseDirectory;

    public function __construct($baseDirectory)
    {
        $this->baseDirectory = $this->normalizePath($baseDirectory) . DIRECTORY_SEPARATOR;
        $this->ensureDirectory($this->baseDirectory);
    }

    protected function normalizePath($path)
    {
        $path = dirname(rtrim($path, '\\/') . DIRECTORY_SEPARATOR . 'xxx');

        return rtrim($path, DIRECTORY_SEPARATOR);
    }

    protected function ensureDirectory($directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0766, true);
        }

        return is_dir($directory) && $this->isWritable();
    }

    /**
     * Check if the container is writable
     */
    public function isWritable()
    {
        return is_writable($this->baseDirectory);
    }

    /**
     * This will check if a file is in the container
     *
     * @param  string $file
     * @return bool
     */
    public function has($file)
    {
        return $file && file_exists($this->baseDirectory . $file);
    }

    /**
     * Saves the $content string as a file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function save($file, $content)
    {
        $file = $this->normalizePath($file);
        $dir = dirname($this->baseDirectory . $file);
        if ($this->ensureDirectory($dir)) {
            return (bool) file_put_contents($this->baseDirectory . $file, $content);
        }

        return false;
    }

    /**
     * Delete the file from the container
     *
     * @param  string $file
     * @return bool
     */
    public function delete($file)
    {
        $file = $this->normalizePath($file);
        if (file_exists($this->baseDirectory . $file)) {
            return unlink($this->baseDirectory . $file);
        }

        return true;
    }

    /**
     * Moves a temporary uploaded file to a destination in the container
     *
     * @param  string $localFile   local path
     * @param  string $destination
     * @return bool
     */
    public function moveUploadedFile($localFile, $destination)
    {
        $dir = dirname($this->baseDirectory . $destination);
        if ($this->ensureDirectory($dir) && file_exists($localFile)) {
            if (is_uploaded_file($localFile)) {
                return move_uploaded_file($localFile, $this->baseDirectory . $destination);
            }
        }

        return false;
    }

}
