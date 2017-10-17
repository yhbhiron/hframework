<?php 
if(!defined('IN_WEB')){
	exit;
}

/**
 * 用户请求数据
 * @author Administrator
 * @version 1.0.0
 * @since 2016-4-10
 */
class request extends model{
	
	/**get数据*/
	protected static $get = array();
	
	/**post数据*/
	protected static $post = array();

	/**$_REQUEST数据*/
	protected static $request = array();

	
	/**
	 * 请求的$_GET数据,不能加载手动的$_GET[key] = val的修改
	 */
	public static function get(){
		
		$args = func_get_args();
		$argNum = count($args);
		
		if(self::$get == null){
			self::$get = array_merge(StrObj::haddslashes(StrObj::striptags($_GET)),self::$get);
		}
		
		if($args == null){
			return self::$get;
		}
		
		if($argNum == 1){
			
			if(validate::isCollection($args[0])){
				return null;
			}
			
			return arrayObj::getItem(self::$get,$args[0]);
		}
		
		
		if($argNum>=2){
			
			self::$get[$args[0]] = StrObj::haddslashes($args[1]);
			return self::$get[$args[0]];
			
		}
		
	}
	
	
	/**
	 * 请求的$_POST数据,不能加载手动的$_POST[key] = val的修改
	 */
	public static function post(){
		
		$args = func_get_args();
		$argNum = count($args);
		
		if(self::$post == null){
			self::$post = array_merge(StrObj::haddslashes($_POST),self::$post);
		}
		
		if($args == null){
			return self::$post;
		}
		
		if($argNum == 1){
			
			if(validate::isCollection($args[0])){
				return null;
			}
			
			return arrayObj::getItem(self::$post,$args[0]);
		}
		
		
		if($argNum>=2){
			
			self::$post[$args[0]] = StrObj::haddslashes($args[1]);
			return self::$post[$args[0]];
			
		}
		
	}
	
	
	/**
	 * 请求的$_REQUEST数据,不能加载手动的$_REQUEST[key] = val的修改
	 */
	public static function req(){
		
		$args = func_get_args();
		$argNum = count($args);
		
		if(self::$request == null){
			self::$request = array_merge(StrObj::haddslashes($_REQUEST),self::$request,self::$get,self::$post);
		}
		
		if($args == null){
			return array_merge(self::$request,self::$request,self::$get,self::$post);
		}
		
		if($argNum == 1){
			
			if(validate::isCollection($args[0])){
				return null;
			}
			
			return arrayObj::getItem(self::$request,$args[0],arrayObj::getItem(self::$get,$args[0],arrayObj::getItem(self::$post,$args[0])));
		}
		
		
		if($argNum>=2){
			
			self::$request[$args[0]] = StrObj::haddslashes($args[1]);
			return self::$request[$args[0]];
			
		}
		
	}	
	
	
	/**
	 * 请求的$_FILES数据,只读
	 */
	public static function files(){
		
		$args = func_get_args();
		$argNum = count($args);
		
		if($args == null){
			return $_FILES;
		}
			
		if(validate::isCollection($args[0])){
			return null;
		}
		
		return arrayObj::getItem($_FILES,$args[0]);
		
		
	}	

	/**
	 * 请求的$_COOKIE数据,只读
	 */
	public static function cookie(){
		
		$args = func_get_args();
		$argNum = count($args);
		
		if($args == null){
			return $_COOKIE;
		}
			
		if(validate::isCollection($args[0])){
			return null;
		}
		
		return arrayObj::getItem($_COOKIE,$args[0]);
		
		
	}	
	
	/**
	 * 是否为ajax请求
	 * @return boolean true/false
	 */
	public static function isAjax(){
		
		$with = arrayObj::getItem($_SERVER,'X_REQUEST_WITH');
		return $with == 'XMLHttpRequest';
		
	}

	
	/**
	 * 是否为post请求
	 * @return boolean true/false
	 */
	public static function isPost(){
		return  strtolower( arrayObj::getItem($_SERVER,'REQUEST_METHOD')) == 'post';
	}	
	
	
	/**
	 * 是否为get请求
	 * @return boolean true/false
	 */
	public static function isGet(){
		return  strtolower( arrayObj::getItem($_SERVER,'REQUEST_METHOD')) == 'get';
	}	

	
	
	/**
	 * 获取用户IP
	 */
	public static function getUserIP(){
		
		if(website::$env == 'cli'){
			return '127.0.0.1';
		}
		
		$ip_reg='/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/';
		if(arrayObj::getItem($_SERVER,'HTTP_CDN_SRC_IP')!=''){
			$onlineip = $_SERVER['HTTP_CDN_SRC_IP'];
		}else if(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
			$onlineip = getenv('HTTP_X_FORWARDED_FOR');
		}else if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
			$onlineip = $_SERVER['REMOTE_ADDR'];
		}else if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
			$onlineip = getenv('HTTP_CLIENT_IP');
		}else if(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
			$onlineip = getenv('REMOTE_ADDR');
		}
		
		if(preg_match($ip_reg,$onlineip)){
			return $onlineip;
		}
		
	}	
}

?>