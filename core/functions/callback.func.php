<?php
if(!defined('IN_WEB')){
	exit;
}
class Callback{

	/**
	 * 事件排序回调函数
	 * @param array $ev1
	 * @param array $ev2
	 */
	public static function eventOrder($ev1,$ev2){
		
		if($ev1['order']<$ev2['order']){
			return 1;
		}else if($ev1['order']>$ev2['order']){
			return -1;
		}
		
		return 0;
	}
	
	public static  function errorHandle($errno, $errstr, $errfile, $errline){
		if(  !($errno & error_reporting()) ){
			return;
		}
		$level = array(
			'1'=>'E_ERROR',
			'2'=>'E_WARNING',
			'4'=>'E_PARSE',  
			'8'=>'E_NOTICE', 
			'16'=>'E_CORE_ERROR',  
			'32'=> 'E_CORE_WARNING', 
			'64'=>'E_COMPILE_ERROR',  
			'128'=>'E_COMPILE_WARNING',  
			'256'=>'E_USER_ERROR',  
			'512'=>'E_USER_WARNING',  
			'1024'=>'E_USER_NOTICE',  
			'2047'=>'E_ALL',  
			'2048'=>'E_STRICT',  
			'8192'=>'E_DEPRECATED',
		    '4096'=>'E_RECOVERABLE_ERROR',
		    '16384'=>'E_USER_DEPRECATED',
		);
		
		
		website::error($level[$errno].';'.$errstr,2,6,$errno);
		return false;
	}
	
	public static  function errorLastHandle(){
		
		$e      = error_get_last();
		$errno  = $e['type'];
		$errstr = $e['message'];
		
		if(website::$config['debug'] == true){
			if( !($errno  & (E_ALL^E_WARNING^E_NOTICE^E_USER_ERROR^E_USER_NOTICE^E_DEPRECATED)) ){
				return;
			}
		}else{
			if( !($errno & error_reporting() & (E_ALL^E_WARNING^E_NOTICE^E_USER_ERROR^E_USER_NOTICE^E_DEPRECATED)) ){
				return;
			}			
		}
		
		$level = array(
			'1'=>'E_ERROR',
			'2'=>'E_WARNING',
			'4'=>'E_PARSE',  
			'8'=>'E_NOTICE', 
			'16'=>'E_CORE_ERROR',  
			'32'=> 'E_CORE_WARNING', 
			'64'=>'E_COMPILE_ERROR',  
			'128'=>'E_COMPILE_WARNING',  
			'256'=>'E_USER_ERROR',  
			'512'=>'E_USER_WARNING',  
			'1024'=>'E_USER_NOTICE',  
			'2047'=>'E_ALL',  
			'2048'=>'E_STRICT',  
			'8192'=>'E_DEPRECATED',
		);
		
		$t = array();
		$t[] =$e;
		ErrorHandler::$errorInfo = $t;
		website::error($level[$errno].';'.$errstr,2,6,$errno);
	}
}	
?>