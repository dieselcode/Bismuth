<?php

namespace Bismuth\Core\Cache;

interface CacheInterface
{
    public function __construct($options = array());
    public function getCache($key);
    public function setCache($key, $value);
    public function getCacheSize();
    public function purgeCache();
}

?>