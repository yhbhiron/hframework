<?php
/**
 * 站点模板配置
 * @author: Hiron Jack
 * @since 2013-7-29
 * @version: 1.0.1
 * @example:
 **/

if(!defined('IN_WEB')){
 exit;
}


return array(
	
	/**模板目录**/
	'tpl_dir'=>VIEW_DIR.'/templates/',
	
	/**编译目录,根据前台和后台变化**/
	'comp_dir'=> !defined('IN_ADMIN') ? WEB_ROOT.'temp/compiled/front/' : WEB_ROOT.'temp/compiled/admin/',
   	
	/**模板插件目录**/
	'plugin_dir'=>website::$config['plugin_dir'].'ui/',
	
	/**模板左连接符**/
	'left_spe'=>'<{',
	
	/**模板右连接符**/
	'right_spe'=>'}>',
	
	/**图片目录**/
	'img_dir'=>VIEW_DIR.'images/',
	
	/**js目录**/
	'js_dir'=>VIEW_DIR.'js/',
	
	/**css目录**/
	'css_dir'=>VIEW_DIR.'style/',

	/**公共js**/
	'js_comm_dir'=>WEB_ROOT.'js/',

	/**公共images**/
	'img_comm_dir'=>WEB_ROOT.'images/',

)

?>