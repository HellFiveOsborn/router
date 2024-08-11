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
    /** @var int|false */
    protected $cacheTime = false;

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
            $mimeType = $this->getMimeType($filePath);
            $lastModified = filemtime($filePath);
            $eTag = md5_file($filePath);

            header("Content-Type: {$mimeType}");
            
            if ($this->cacheTime !== false) {
                header("Cache-Control: public, max-age={$this->cacheTime}");
                header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
                header("ETag: \"{$eTag}\"");

                if ($this->notModified($lastModified, $eTag)) {
                    header("HTTP/1.1 304 Not Modified");
                    exit;
                }
            } else {
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                header("Cache-Control: post-check=0, pre-check=0", false);
                header("Pragma: no-cache");
            }

            $this->readFileChunked($filePath);
            exit;
        }
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function getMimeType(string $filePath): string
    {
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'htm' => 'text/html',
            'xml' => 'application/xml',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            'zip' => 'application/zip',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if (isset($mimeTypes[$extension])) {
            return $mimeTypes[$extension];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType ?: 'application/octet-stream';
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
     * @param int|false $time
     * @return Router
     */
    public function setCache(int|false $time): Router
    {
        $this->cacheTime = $time;
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