<?php

include '../vendor/autoload.php';

$cache = new \Bismuth\Tools\HTTPCache();

$object = new \Bismuth\Tools\Object();
$object->headers = array('foo' => 'bar');
$object->response = new \stdClass();
$object->response->foo = 'bar';

$file = $cache->generateFileName(__FILE__);

$cache->setCache($file, $object);

var_dump( $cache->getCache(__FILE__) );

?>