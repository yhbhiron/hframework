<?php

if(!defined('IN_WEB')){
 exit;
}
/**
 * 名称=>array(
 * 	'from_uri'=>'伪静地址',
 *  'url_to'=>array(
 *  	'act'=>'行为',
 *  	'app'=>'应用控制器',
 *  	'status'=>'可选，http的状态',
 *  	'url'=>'重定向地址,如果设置了他，则act,system,app设置无效',
 *  	
 *  )
 * )
 */
return array (

    'default' => array(
        'sort' => '-1',
        'from_uri' =>array('([a-z]+)_([a-z_]+)\/?','([a-z]+)\/?'),
        'get_uri' => function ($callback, $key, $act,$app,$params = array()) {
        
        $key = preg_replace('/(?<=[^A-Z])([A-Z])/', ' $1', $key);
        $list = array_map(function ($v) {
            return trim(strtolower($v), '_');
        }, preg_split('/ /', $key));
            
            return website::$route->buildUrlParams(website::$url['host'] . implode('_', $list), $params);
        }
        
    ),
) ;
?>