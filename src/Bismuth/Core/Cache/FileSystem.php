<?php

namespace Bismuth\Core\Cache;

class FileSystem implements CacheInterface
{

    public $options     = array(
        'cache_path'    =>  '',
        'cache_ext'     =>  '.cache',
        'cache_max_age' =>  3600
    );

    public function __construct($options = array())
    {
        $this->options = $options;

        if (empty($this->options['cache_path'])) {
            $this->options['cache_path'] = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/cache/';
        }

        if (empty($this->options['cache_ext'])) {
            $this->options['cache_ext'] = '.cache';
        }

        if (empty($this->options['cache_max_age'])) {
            $this->options['cache_max_age'] = 3600;
        }

        clearstatcache();

        if (!is_writable($this->options['cache_path'])) {
            mkdir($this->options['cache_path'], 0777);
        } else {
            touch($this->options['cache_path']);
        }
    }

    public function getCache($file)
    {
        $filePath = $this->options['cache_path'] . $this->generateFileName($file);

        clearstatcache();

        if (file_exists($filePath)) {
            if ((time() - filemtime($filePath)) < $this->options['cache_max_age']) {
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
        $filePath = $this->options['cache_path'] . $this->generateFileName($file);

        if (!$this->isSerialized($content) && $forceSerialize) {
            $content = serialize($content);
        }

        file_put_contents($filePath, $content);
    }

    public function generateFileName($path)
    {
        return md5($path) . $this->options['cache_ext'];
    }

    function isSerialized($data)
    {
        $content = @unserialize($data);
        return ($data === 'b:0;' || $content !== false) ? true : false;
    }

    public function getCachePath()
    {
        return $this->options['cache_path'];
    }

}

?>