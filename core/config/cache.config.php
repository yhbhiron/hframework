<?php
return array(
	
	/**缓存级别**/
	'level'=>array(
		//'1'=>'memoryCache',
		'1'=>'redisCache',
		'2'=>'fileCache',
		'3'=>'dbCache',
	),
	
	'class_dir'=>WEB_CLASS_DIR.'cache/',

);
?>