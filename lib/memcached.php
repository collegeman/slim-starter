<?php
$mem = new Memcached;
$mem->addServer(GA_LIB_MEMCACHED, '11211');

function cache($key, $value, $timeout = 0) {
  global $mem;
  return $mem->set($key, $value, $timeout);
}

function cached($key, $default = false) {
  global $mem;
  $value = $mem->get($key);
  return is_null($value) ? $default : $value;
}

function dump($key) {
  global $mem;
  return $mem->delete($key);
}