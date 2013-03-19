<?php

namespace Bismuth\Core\Cache;

use Bismuth\Tools\Options;

class FileSystem implements CacheInterface
{

    public $options = null;

    public function __construct($userOpts = array())
    {
        $this->options = new Options(array(
            'cache_path'     => dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/cache/',
            'cache_max_age'  => '3600',
            'cache_max_size' => 0
        ));

        $this->options->setBulkOptions($userOpts);

        clearstatcache();

        if (!is_writable($this->options->cache_path)) {
            mkdir($this->options->cache_path, 0777);
        } else {
            touch($this->options->cache_path);
        }

        // make sure the existing cache isn't too large
        if ($this->options->cache_max_size !== false && $this->getCacheSize() >= $this->options->cache_max_size) {
            //$this->purgeCache();
        }
    }

    public function getCache($file)
    {
        $filePath = $this->options->cache_path . $this->generateFileName($file);

        clearstatcache();

        if (file_exists($filePath)) {
            if ((time() - filemtime($filePath)) < $this->options->cache_max_age) {
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
        $filePath = $this->options->cache_path . $this->generateFileName($file);

        if (!$this->isSerialized($content) && $forceSerialize) {
            $content = serialize($content);
        }

        file_put_contents($filePath, $content);
    }

    public function getCacheSize()
    {
        $filter = $this->options->cache_path . '*.bcache';
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
        $filter = $this->options->cache_path . '*.bcache';
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
        return $this->options->cache_path;
    }

}

?>