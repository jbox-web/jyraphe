<?php

function __autoload($name) {
  $file = dirname(__FILE__).'/'.$name.'.php';
  if(file_exists($file)) {
    require_once $file;
  }
}

?>