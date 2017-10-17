<?php

if(!defined('IN_WEB')){
	exit;
}

class reader{
	
	
	/**
	 * 读写mysql自带的slowlog
	 * @param string $file
	 */
	public static function mysqlSlowlog($file){
		
		$GLOBALS['slow_log_temp'] = array();
		return filer::readFileLine($file,function($line,$data){
			
			if(trim($line) == ''){
				return $data;
			}
			
			if(substr($line,0,1) == '#'){
				
				if($GLOBALS['slow_log_temp']!=null){
					
					$data[md5($GLOBALS['slow_log_temp']['sql'])] = $GLOBALS['slow_log_temp'];
					$GLOBALS['slow_log_temp'] = array();
					
				}
				
				if(preg_match('/Query_time:\s*([^\s]+)/',$line,$m)){
					$GLOBALS['slow_log_temp']['query_time'] = trim($m[1]);
				}  
				
				if(preg_match('/Lock_time:\s*([^\s]+)/',$line,$m)){
					$GLOBALS['slow_log_temp']['lock_time'] = trim($m[1]);
				}
				
				if(preg_match('/Rows_sent:\s*([^\s]+)/',$line,$m)){
					$GLOBALS['slow_log_temp']['rows_sent'] = trim($m[1]);
				}
				
				if(preg_match('/Rows_examined:\s*([^\s]+)?/',$line,$m)){
					$GLOBALS['slow_log_temp']['rows_examined'] = trim($m[1]);
				}
				
				if(isset($GLOBALS['slow_log_temp']['query_time']) && isset($GLOBALS['slow_log_temp']['lock_time'])){
					$GLOBALS['slow_log_temp']['sql_query_time']	 = $GLOBALS['slow_log_temp']['query_time'] - $GLOBALS['slow_log_temp']['lock_time'];	
				}
				
				if(isset($GLOBALS['slow_log_temp']['rows_sent']) && isset($GLOBALS['slow_log_temp']['rows_examined'])){
					
					if($GLOBALS['slow_log_temp']['rows_examined'] == 0){
						$GLOBALS['slow_log_temp']['query_effect_rate'] =  1;
					}else{
						$GLOBALS['slow_log_temp']['query_effect_rate'] = $GLOBALS['slow_log_temp']['rows_sent'] / $GLOBALS['slow_log_temp']['rows_examined'];
					}
				}
				
				return $data;
			}
			
			if(!preg_match('/SET timestamp/',$line)){
				
				if(!isset($GLOBALS['slow_log_temp']['sql'])){
					$GLOBALS['slow_log_temp']['sql'] = '';
				}
				
				$GLOBALS['slow_log_temp']['sql'].= $line;
				
			}
			
			return $data;
			
		});
	} 
	
	
}
