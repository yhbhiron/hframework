<?php
/**
 * 本文件主要是用于公共加载文件的引用文件
 * @author: Hiron Jack
 * @since 2013-7-3
 * @version: 2.0.1
 * @example:
 */

if(!defined('IN_WEB')){
	exit;
}

/**声明网站的根目录**/
!defined('CORE_DIR') && define('CORE_DIR',dirname(__FILE__).'/');
$coreDir = strrchr(str_replace('\\','/',realpath(CORE_DIR)),'/');
!defined('WEB_ROOT') && define('WEB_ROOT',substr(realpath(CORE_DIR),0,-strlen(trim($coreDir,'/'))));

/**静态文件存放目录**/
!defined('STATIC_PATH') && define('STATIC_PATH',WEB_ROOT.'static/');

/**插件存放目录**/
!defined('PLUG_DIR') && define('PLUG_DIR',WEB_ROOT.'plugins/');

/**基础类存放目录**/
define('WEB_CLASS_DIR',CORE_DIR.'/classes/');

/**站内基础配置存放目录**/
define('WEB_CONFIG_DIR',CORE_DIR.'/config/');

/**基础函数存放目录**/
define('WEB_FUNC_DIR',CORE_DIR.'/functions/');

/**公共类库存放目录**/
define('WEB_LIB_DIR',CORE_DIR.'/libs/');

/**模块目录**/
!defined('MODULE_DIR') && define('MODULE_DIR',WEB_ROOT.'systems/');

!defined('APP_PATH') && define('APP_PATH',WEB_ROOT);
if(!defined('APP_BASE_PATH')){
	define('APP_BASE_PATH',APP_PATH.'base');
}



/**加载网站类**/
require(WEB_CLASS_DIR.'website.class.php');
spl_autoload_register(array('website','autoload'));

/**初使化网站**/
website::init(isset($argv[1]) ? $argv[1] : null);

/**模板主题处理**/
!defined('VIEW_DIR') && define('VIEW_DIR',WEB_ROOT.'theme/front/');

if(isset($beforeInit) && is_callable($beforeInit)){
	$beforeInit();
}

/**cli模式下的参数处理**/
if(website::$env == website::ENV_CLI){
	
	
	if(request::get('debug')>0){
		if(request::get('debug') != 1){
			website::$config['debug'] = false;
		}else{
			website::$config['debug'] = true;
		}
	}
	
	/**执行建项目命令**/
	if(request::get('build-web')!=''){
		
		website::buildWebsite(request::get('build-web'));
		exit('Build Website '.request::get('build-web').' success!');
		
	}
	
	/**执行重构核心命令*/
	if(request::get('rebuild-web')!=''){
	
		website::rebuildCore(request::get('rebuild-web'));
		exit('Rebuild Website '.request::get('rebuild-web').' success!');
	
	}
	
	
	/**执行创建app*/
	if(request::get('create-app')!=''){
	
		website::createApp(request::get('create-app'));
		exit('Create App '.request::get('create-app').' success!');
	}
	
	/**输出当前应用的数据库sql*/
	if(request::get('output-sql')!=''){
		echo  db::instance()->dumpData();
		exit;
	}
	
	website::doEvent('cli');
}



?>