<?php
/**
 * 数据库连接配置文件
 */
return array(

	/**慢查询的查询文件所存放的位置**/
	'log-slow-queries' => WEB_ROOT.'temp/logs/mysql-slow/',

	/**设置要写入日志的查询秒数**/
    'long_query_time' => 1,   
	
	/**是否记录无索引的查询**/
    'log-queries-not-using-indexes'=>0,


	'databases'=>array(

		'hiron'=>array(
	
			'type'=>'pdomysql',
			'connect'=>array(
	
				'main'=>array(
					/**主机**/
					'host'=>'localhost',
					
					/**登录用户**/
					'user'=>'root',
				
					/**密码**/
					'passwd'=>'127956',
				
					/**默认数据库**/
					'database'=>'hiron',
					
					/**默认校对字符集**/
					'charset'=>'utf8',
		
					'pconnect'=>false,
					
					'db_pre'=>'guu_',
				),
				
				'slaves'=>array(
				
					'slave1'=>array(
						/**主机**/
						'host'=>'192.168.0.103',
						
						/**登录用户**/
						'user'=>'yhbhiron',
					
						/**密码**/
						'passwd'=>'127956',
					
						/**默认数据库**/
						'database'=>'hiron',
						
						/**默认校对字符集**/
						'charset'=>'utf8',
				
						'pconnect'=>false,
					),			
					
				),
				
			),
			
	
	     ),
	     
		'cache'=>array(
				'type'=>'mysql',
	     		'connect'=>array(
					'main'=>array(
						/**主机**/
						'host'=>'localhost',
						
						/**登录用户**/
						'user'=>'root',
					
						/**密码**/
						'passwd'=>'127956',
					
						/**默认数据库**/
						'database'=>'hiron_cache',
						
						/**默认校对字符集**/
						'charset'=>'utf8',
			
						'pconnect'=>false,
		     			'db_pre'=>'',
					),
				),
			),
				     
		'hiron_session'=>array(
				'type'=>'mysql',
				'connect'=>array(
					'main'=>array(
						/**主机**/
						'host'=>'localhost',
						
						/**登录用户**/
						'user'=>'root',
					
						/**密码**/
						'passwd'=>'127956',
					
						/**默认数据库**/
						'database'=>'hiron_session',
						
						/**默认校对字符集**/
						'charset'=>'utf8',
			
						'pconnect'=>false,
						'db_pre'=>'session_',
					),
				),
			),	 
			
		'wb'=>array(
				'type'=>'pdomysql',
				'connect'=>array(
					'main'=>array(
						/**主机**/
						'host'=>'192.168.20.84',
						
						/**登录用户**/
						'user'=>'wbuser',
					
						/**密码**/
						'passwd'=>'wb123456',
					
						/**默认数据库**/
						'database'=>'wbiao_kohana',
						
						/**默认校对字符集**/
						'charset'=>'utf8',
			
						'pconnect'=>false,
						
						'db_pre'=>'wb_',
					),
				),
			),	
	),	
)

?>