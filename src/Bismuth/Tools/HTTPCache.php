<?php

namespace Bismuth\Tools;

class HTTPCache
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
            $this->options['cache_path'] = dirname(dirname(dirname(dirname(__FILE__)))) . '/cache/';
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
                return unserialize(file_get_contents($filePath));
            }
        }

        return false;
    }

    public function setCache($file, $contents)
    {
        $filePath = $this->options['cache_path'] . $this->generateFileName($file);
        file_put_contents($filePath, serialize($contents));
    }

    public function generateFileName($path)
    {
        return md5($path) . $this->options['cache_ext'];
    }

    public function getCachePath()
    {
        return $this->options['cache_path'];
    }

}

?>