<?php
/**
 * 站点公共配置
 * @author: Hiron Jack
 * @since 2013-7-4
 * @version: 1.0.0
 * @example:
 */

return array(
	
	

	/**调试模式**/
	'debug'=>true,
		
	/**是否将调试信息写入文件**/
	'debug_log_path'=>WEB_ROOT.'/temp/logs/debug/',

	'debug_tpl_file'=>CORE_DIR.'/template/debug.php',

	'memory'=>'164M',

	'timezone'=>'PRC',

	/**开启日志**/
	'logs'=>true,
	
	/**开启日志情况下，决定日志记录类型 1为文件,2为数据库**/
	'logs_type'=>1,
	
	/**是否显示错误**/
	'show_errors'=>1,
	
	/**开启错误情况下，设置错误的级别**/
	'error_level'=>E_ALL^E_DEPRECATED,
	
	/**是否通过邮件错误通知**/
	'error_email'=>0,
	
	/**错误代码表配置文件**/
	'error_code_cnf'=>'errorCode',
	
	/**错误类型表配置文件**/
	'error_type_cnf'=>'errorType',
	
	'error_tpl_dir'=>CORE_DIR.'template/error.php',
	
	/**当使用日志时，日志的保存目录**/
	'error_log_dir'=>WEB_ROOT.'temp/logs/errors/',
	
	/**默认后台系统**/
	'def_app'=>'index',

	/**系统编码**/
	'charset'=>'utf-8',
	
	/**地址重写类型 1-常规,2-重写**/
	'rewrite_type'=>2,

	/**插件目录**/
	'plugin_dir'=>WEB_ROOT.'plugins/',
	
	/**静态化处理函数库文件路径**/
	'static_callback_dir'=>PLUG_DIR.'static/',

	/**events事件目录**/
	'event_path'=>PLUG_DIR.'events/',

	/**数据源目录**/
	'dbs_path'=>WEB_ROOT.'data/datasource/',
	
	/**缓存目录**/
	'cache_dir'=>WEB_ROOT.'temp/cache/',

   /**缓存hash表**/
	'cache_hash'=>'cacheHash',

	/**上传目录**/
	'upload_dir'=>WEB_ROOT.'data/upload/',

	/**系统密匙*/
	'secret'=>'&($%$^#%&)^*^HHsfsdfa12312$^%$#&&',
	
	/**系统数据库前缀**/
	'db_pre'=>'guu_',
	
    /**系统默认数据库**/
	'def_db'=>'hiron',

);


?>