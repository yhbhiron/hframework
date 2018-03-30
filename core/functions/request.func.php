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
class Request extends Model{
	
	/**get数据*/
	protected static $get = array();
	
	/**post数据*/
	protected static $post = array();

	/**$_REQUEST数据*/
	protected static $request = array();
	
	/**CLI**/
	const PLATE_CLI = 1;
	
	/**安卓APP*/
	const PLATE_APP_ANDROID = 2;
	
	/**苹果APP*/
	const PLATE_APP_IOS = 3;
	
	/**安卓H5*/
	const PLATE_H5_ANDROID = 4;
	
	/**苹果H5*/
	const PLATE_H5_IOS = 5;
	
	/**PC*/
	const PLATE_PC = 6;
	
	/**微个公众号*/
	const PLATE_H5_WX = 7;
	
	/**微信小程序*/
	const PLATE_WX_MAPP = 8;
	
	/**支付宝小程序*/
	const PLATE_ALI_MAPP = 9;
	
	/**
	 * 保存用户请求的平台
	 */
	public static $plateForm;
	
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
	
	
	/**
	 * 获取用户的请求平台类型
	 * 如果是app使用加请求头信息 REQUEST_PLATE:android_app安卓app,ios_app苹果app
	 * 常规浏览器用user_agent识别
	 * @return int
	 */
	public static function getPlateForm(){
	    
	    if(self::$plateForm!=null){
	        return self::$plateForm;
	    }
	    
	    $userAgent = ArrayObj::getItem($_SERVER,'HTTP_USER_AGENT');
	    $reqPlate = ArrayObj::getItem($_SERVER,'REQUEST_PLATE');
	    
	    if($reqPlate == 'android_app'){
	       self::$plateForm =  self::PLATE_APP_ANDROID;
	    }else if($reqPlate == 'ios_app'){
	       self::$plateForm =   self::PLATE_APP_IOS;
	    }else if(preg_match('/MicroMessenger/i',$userAgent)){
	        
	        if(preg_match('/miniProgram/i',$userAgent)){
	            self::$plateForm = self::PLATE_WX_MAPP;
	        }else{
	           self::$plateForm = self::PLATE_H5_WX;
	        }
	        
	    }else if(preg_match('/MicroMessenger/i',$userAgent)){
	        self::$plateForm = self::PLATE_H5_WX;
	    }else if(preg_match('/android/i',$userAgent)){
	        self::$plateForm = self::PLATE_H5_ANDROID;
	    }else if(preg_match('/iPhone|ipad/i',$userAgent)){
	        self::$plateForm = self::PLATE_H5_IOS;
	    }else if($userAgent == null){
	       self::$plateForm =   self::PLATE_CLI;
	    }else{
	       self::$plateForm =   self::PLATE_PC;
	    }
	    
	    return self::$plateForm;
	}
	
	
	/**
	 * 获取当前平台的名称
	 * @param int $plate 平台类型
	 * @return string
	 */
	public static function getPlateFormName($plate=null){
	    
	    $plate = $plate ? $plate : self::getPlateForm();
	    $ref = new ReflectorExt('Request');
        return $ref->getConstComment('PLATE_',$plate);
	}
}

?>