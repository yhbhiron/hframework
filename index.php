<?php

/**
 * 本文件为入口文件
 * @author: Hiron Jack
 * @since 2013-7-3
 * @version: 1.0.3
 * @example:
 */
define('IN_WEB',true);
define('APP_PATH',dirname(__FILE__).'/');
define('VIEW_DIR',APP_PATH.'views/');

$_SERVER['env'] = 'local';

/**引用公共启动文件**/
require('core/init.php');

website::run();
?>