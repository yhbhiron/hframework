<?php
/**
 * 静态化配置
 * key用于静态化调用的名称
 * callback 处理静态化的回调函数
 * type 静态化的类型,list或page
 * name 静态化的名称，用于保存静态文件的目录名称
 * static_file生成静态文件的名称
 */
if(!defined('IN_WEB')){
 exit;
}


$staticRuleConfig = array(
	'product_list'=>array('callback'=>'productList','type'=>'list','name'=>'prodlist','static_file'=>'watch-b%s-p%s.html'),
);


?>