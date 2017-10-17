<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * memcache session
 * 依赖 memoryCache
 * @author Administrator
 * @since 2014-11-11
 * @version 1.0.0
 */
class sessionMemcache {
	
	protected static $driver;
	
	public static $sid;
	
	
	public static function open($save_path, $session_name){
		
		if(self::$driver == null){
			self::$driver = new cacheMemory();
		}
		
	}
	
	public static function setSid($sid){
		self::$sid = $sid;
	}
	
	public static function set($name,$val,$time=120,$delOnGet=false){
		
		if(self::$sid==''){
			return false;
		}
		
		 $sessName     = md5(self::$sid.'_'.$name);
		 $data = array();
		 $data['val'] = $val;
		 $data['time'] = $time;
		 $data['del_onget'] = $delOnGet;
		 $data['is_session'] = 1;
		 $_SESSION[$name] = $val;
		 
		 return self::$driver->write($sessName,$data,$time);		
	}
	
	
	public static function get($name){
		
		$sessName  = md5(self::$sid.'_'.$name);
		$data = self::$driver->read($sessName);
		
		if($data['del_onget'] == true){
			self::delete($name);	
		}
		
		return $data['val'];
		
	}
	
	public static function delete($name){
		
		$sessName  = md5(self::$sid.'_'.$name);
		self::$driver->delete($sessName);
		session_unregister($name);
		
		return true;
	}
	
	public static function read($id){		
				
		return false;
	}
	
	public static function write($id,$data){

		return false;
	}
	

	public static function destroy($id)
	{
		return false;  
	}
	
	public static function gc($maxlifetime=0){
		  
		  $t = website::curRunTime();
		  self::$driver->explore(array('sessionMemcache','expCallback'));
		  website::debugAdd('memsession回收session',$t);
		  return true;
		  
	}
	
	public static function expCallback($key){
		
		$data = self::$driver->read($key);
		if($data!=null && is_array($data) && arrayObj::getItem($data,'is_session') == true){
			self::$driver->write($key,$data,$data['time']);
		}
		
	}
	
	
	public static function close()
	{
	  return true;
	}

	
	
}