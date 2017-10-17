<?php !defined('IN_WEB') && exit('Access Deny!');
class sessionFile {
	
	protected static $path;
	
	public static $sid;
	
	protected static $config;
	
	public static function open($save_path, $session_name){
		
		self::$config = website::loadConfig('session.sessionFile',false);
		$path = self::$config['session_path'];
		if(!is_dir($path) && !is_readable($path)){
			return false;
		}
		
		self::$path = $path;
		return false;
	}
	
	public static function setSid($sid){
		self::$sid = $sid;
	}
	
	public static function set($name,$val,$time=120,$delOnGet=false){
		
		if(self::$sid==''){
			return false;
		}
		
		 $file = self::$path.'/'.'sess_'.self::$sid.'_'.md5($name);
		 
		 $data = array();
		 $data['hiron_sess_expire_time'] = time()+$time;
		 $data['hiron_sess_set_time']    = $time;
		 $data['hiron_sess_data'] = $val;
		 $data['hiron_sess_key'] = $name;
		 $data['del_onget'] = $delOnGet;
		 $_SESSION[$name] = $val;
		 
		 return filer::writeFile($file,serialize($data));		
	}
	
	
	public static function get($name){
		
		$file = self::$path.'/'.'sess_'.self::$sid.'_'.md5($name);
		if(!is_file($file)){
			return;
		}
		
		$data = @unserialize(filer::readFile($file));
		if($data == null || $data['hiron_sess_expire_time']<time()){
			@unlink($file);
			return;
		}
		
		if($data['del_onget'] == true){
			self::delete($name);
		}
		
		return $data['hiron_sess_data'];
		
	}
	
	public static function delete($name){
		$file = self::$path.'/'.'sess_'.self::$sid.'_'.md5($name);
		function_exists('session_unregister') && @session_unregister($name);
		unset($_SESSION[$name]);
		return @unlink($file);
	}
	
	public static function read($id){
		
		$files = glob(self::$path."/sess_$id*");
		if($files == null){
			return;	
		}
		
		foreach ($files as $filename) {
		  	$data = @unserialize(file_get_contents($filename));
		  	if($data != null && $data['hiron_sess_expire_time']>time()){
		  		
		  		$_SESSION[$data['hiron_sess_key']] = $data['hiron_sess_data'];
		  		self::set($data['hiron_sess_key'],$data['hiron_sess_data'],$data['hiron_sess_set_time']);
		  	}
	
		}
				
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
		
		$files = glob(self::$path."/sess_*");
		if($files == null){
			return false;	
		}
		 
		  foreach ($files as $filename) {
		  	
		  	$data = @unserialize(file_get_contents($filename));
		  	if($data == null || $data['hiron_sess_expire_time']<time()){
		  		@unlink($filename);
		  	}
	
		  }
		  
		  return false;
		  
	}
	
	
	
	public static function close()
	{
	  return false;
	}

	
	
}
