<?php

namespace Bismuth\Core\Cache;

use Bismuth\Tools\Options;

class FileSystem implements CacheInterface
{

    public $options = null;

    public function __construct($userOpts = array())
    {
        $this->options = new Options(array(
            'cachePath'     => dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/cache/',
            'cacheMaxAge'  => '3600',
            'cacheMaxSize' => 0
        ));

        $this->options->setBulkOptions($userOpts);

        clearstatcache();

        if (!is_writable($this->options->cache_path)) {
            mkdir($this->options->cachePath, 0777);
        } else {
            touch($this->options->cachePath);
        }

        // make sure the existing cache isn't too large
        if ($this->options->cacheMaxSize !== false && $this->getCacheSize() >= $this->options->cacheMaxSize) {
            $this->purgeCache();
        }
    }

    public function getCache($file)
    {
        $filePath = $this->options->cachePath . $this->generateFileName($file);

        clearstatcache();

        if (file_exists($filePath)) {
            if ((time() - filemtime($filePath)) < $this->options->cacheMaxAge) {
                $content = file_get_contents($filePath);


                if ($this->isSerialized($content)) {
                    $content = unserialize($content);
                }

                return $content;
            }
        }

        return false;
    }

    public function setCache($file, $content, $forceSerialize = false)
    {
        $filePath = $this->options->cachePath . $this->generateFileName($file);

        if (!$this->isSerialized($content) && $forceSerialize) {
            $content = serialize($content);
        }

        file_put_contents($filePath, $content);
    }

    public function getCacheSize()
    {
        $filter = $this->options->cachePath . '*.bcache';
        $fileList = glob($filter);
        $cacheSize = 0;

        if (is_array($fileList)) {
            foreach ($fileList as $file) {
                $cacheSize += filesize($file);
            }
        }

        return $cacheSize;
    }

    public function purgeCache()
    {
        $filter = $this->options->cachePath . '*.bcache';
        $fileList = glob($filter);

        if (is_array($fileList)) {
            foreach ($fileList as $file) {
                touch($file);
                unlink($file);
            }
        }
    }

    public function generateFileName($path)
    {
        return md5($path) . '.bcache';
    }

    public function isSerialized($data)
    {
        $content = @unserialize($data);
        return ($data === 'b:0;' || $content !== false) ? true : false;
    }

    public function getCacheFileName($file)
    {
        return $this->generateFileName($file);
    }

    public function getCachePath()
    {
        return $this->options->cachePath;
    }

}

?>