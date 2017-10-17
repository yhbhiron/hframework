<?php

if(!defined('IN_WEB')){
 exit;
}
return array (
  'index' => 
  array (
    'level' => 3,
    'cache_type' => 
    array (
      0 => 2,
    ),
    'expire' => 10,
    'data_source'=>'select*from guuoo.figure',
    'type'=>1
  ),
  'figures' => 
  array (
    'level' => 2,
    'cache_type' => 
    array (
      0 => 4,
    ),
  ),
) ;
?>