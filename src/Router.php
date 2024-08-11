<?php

namespace CoffeeCode\Router;

/**
 * Class CoffeeCode Router
 *
 * @author Robson V. Leite <https://github.com/robsonvleite>
 * @package CoffeeCode\Router
 */
class Router extends Dispatch
{
    /**
     * Router constructor.
     *
     * @param string $projectUrl
     * @param null|string $separator
     */
    public function __construct(string $projectUrl, ?string $separator = ":")
    {
        parent::__construct($projectUrl, $separator);
    }

    /**
     * @param string $assetsPath
     */
    public function assets(string $assetsPath): void
    {
        $path = parse_url($this->path, PHP_URL_PATH);
        $filePath = realpath($assetsPath . ltrim($path, '/'));

        if ($filePath && is_file($filePath) && strpos($filePath, $assetsPath) === 0) {
            // Get the MIME type of the file
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);

            // Configure headers for caching
            $lastModified = filemtime($filePath);
            $eTag = md5_file($filePath);

            header("Content-Type: {$mimeType}");
            header("Cache-Control: public, max-age=31536000");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
            header("ETag: \"{$eTag}\"");

            // Check if the file has been modified
            if ($this->notModified($lastModified, $eTag)) {
                header("HTTP/1.1 304 Not Modified");
                exit;
            }

            // Serve the file
            $this->readFileChunked($filePath);
            exit;
        }
    }

    /**
     * @param int $lastModified
     * @param string $eTag
     * @return bool
     */
    private function notModified(int $lastModified, string $eTag): bool
    {
        $ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ?
            strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
        $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) ?
            trim($_SERVER['HTTP_IF_NONE_MATCH']) : false;

        if ($ifModifiedSince && $ifNoneMatch) {
            return ($ifNoneMatch == $eTag && $ifModifiedSince >= $lastModified);
        } elseif ($ifNoneMatch) {
            return $ifNoneMatch == $eTag;
        } elseif ($ifModifiedSince) {
            return $ifModifiedSince >= $lastModified;
        }

        return false;
    }

    /**
     * @param string $file
     */
    private function readFileChunked(string $file): void
    {
        $chunkSize = 8192; // 8KB chunks
        $handle = fopen($file, 'rb');

        if ($handle === false) {
            return;
        }

        while (!feof($handle)) {
            echo fread($handle, $chunkSize);
            flush();
        }

        fclose($handle);
    }

    /**
     * @param string $assetsPath
     * @return Router
     */
    public function setAssets(string $assetsPath): Router
    {
        $this->assetsPath = rtrim($assetsPath, '/') . '/';
        return $this;
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function get(
        string $route,
        callable|string $handler,
        string $name = null,
        array|string $middleware = null
    ): void {
        $this->addRoute("GET", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function post(
        string $route,
        callable|string $handler,
        string $name = null,
        array|string $middleware = null
    ): void {
        $this->addRoute("POST", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function put(
        string $route,
        callable|string $handler,
        string $name = null,
        array|string $middleware = null
    ): void {
        $this->addRoute("PUT", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function patch(
        string $route,
        callable|string $handler,
        string $name = null,
        array|string $middleware = null
    ): void {
        $this->addRoute("PATCH", $route, $handler, $name, $middleware);
    }

    /**
     * @param string $route
     * @param callable|string $handler
     * @param string|null $name
     * @param array|string|null $middleware
     */
    public function delete(
        string $route,
        callable|string $handler,
        string $name = null,
        array|string $middleware = null
    ): void {
        $this->addRoute("DELETE", $route, $handler, $name, $middleware);
    }
}