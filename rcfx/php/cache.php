<?php

function cache_dir(){
  if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
    $path = str_replace('\rcfx\php', '\usr\cache\\', dirname(__FILE__));
  }
  else{
    $path = str_replace('/rcfx/php', '/usr/cache/', dirname(__FILE__));
  }

  if(!is_writable($path)) throw new Exception('Permission required for cache to work properly.');

  return $path;
}

function cache_set($key = null, $value){

  if($key == null) $key = uniqid();

  $path = cache_dir() . $key . '.txt';
  log_bench_start('cache_set#encode#count' . count($value));
  $value = json_encode($value);
  log_bench_end();
  log_bench_start('cache_set#store');
  file_put_contents($path, $value);
  log_bench_end();

  return $key;
}

function cache_get($key){
  //$value = apc_fetch($key);
  $path = cache_dir() . $key . '.txt';
  if(file_exists($path)){
    log_bench_start('cache_set#fetch');
    $value = file_get_contents($path);
    log_bench_end();
    log_bench_start('cache_set#decode');
    $value = json_decode($value, true);
    log_bench_end();
    return $value;
  }
  return null;
}

cache_dir();

?>