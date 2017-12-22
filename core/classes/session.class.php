<?php
/**
 * session处理类,在website::init时初始化,通过set_session_handler
 * 设置对应的session方法
 * @author yhb
 *
 */
class Session {
	
	protected static $sessDriver;
	
	public static $sid;
	
	public static function open($save_path, $session_name){
		
		$type       = website::$config['session_type'];
		$driverName = 'session'.ucfirst($type);
		
		if(!class_exists($driverName)){
			website::error('session '.$type.'不存在的session驱动',2,1);
			return;
		}
		
		if(!is_object(self::$sessDriver) || session::$sessDriver == null){
			self::$sessDriver = new $driverName();
		}
		
		if(!isset(self::$sid)){
			self::$sid  = arrayObj::getItem($_COOKIE,$session_name);
		}
		
		self::$sessDriver->setSid(self::$sid);		
		return self::$sessDriver->open($save_path,$session_name);
		
	}
	
	public static function set($name,$val,$time=120,$delOnGet=false){
		
		if(self::$sessDriver ==null){
			return false;
		}
		
		return call_user_func( array(self::$sessDriver,'set'),$name,$val,$time,$delOnGet);
	}
	
	
	
	/**
	 * 读取session
	 * @param $name
	 */
	public static function get($name){
		
		if(isset($_SESSION[$name])){
			return $_SESSION[$name];
		}
		
		return self::$sessDriver->get($name);
		
	}
	
	
	/**
	 * 删除session
	 * @param $name
	 */
	public static function delete($name){
		return self::$sessDriver->delete($name);
	}
	
	
	/**
	 * 读取session
	 * @param $id
	 */
	public static function read($id){
		return self::$sessDriver->read($id);
	}
	
	
	/**
	 * 写入session
	 * @param $id
	 * @param $data
	 */
	public static function write($id,$data){
		return self::$sessDriver->write($id,$data);
	}
	
		
	public static function destroy($id)
	{
		return self::$sessDriver->destroy($id);
	}
	
	public static function gc($maxlifetime=0){
		$t = website::curRunTime();
		$res = self::$sessDriver->gc($maxlifetime);
		website::debugAdd('回收session',$t);
		return $res;
		 
	}
	
	
	
	public static function close()
	{
		return self::$sessDriver->close();
	}

	
	
}