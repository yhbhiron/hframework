<?php !defined('IN_WEB') && exit('Access Deny!');
class sessionDb {
	
	protected static $sessDriver;
	
	public static $sid;
	
	protected static $config;
	
	public static $dbname = 'session_default';
	
	
	public static function open($save_path, $session_name){
		
		self::$config = website::loadConfig('session.sessionDb',false);
		$dbkey = self::$config['session_dbkey'];
		
		if(self::$sessDriver == null){
			self::$sessDriver = db::instance($dbkey);
		}
		
		
	}
	
	public static function setSid($sid){
		self::$sid = $sid;
	}
	
	public static function set($name,$val,$time=120,$delOnGet=false){
		
		if(self::$sid==''){
			return false;
		}
		
		 $sessName = md5($name);
		 $data = array();
		 $data['sess_expire'] = time()+$time;
		 $data['sess_time']    = $time;
		 $data['sess_val'] = serialize($val);
		 $data['sess_key'] = $sessName;
		 $data['sess_realkey'] = $name;
		 $data['sess_id']  = self::$sid;
		 $data['sess_id']  = $delOnGet;
		 
		 $_SESSION[$name] = $val;
		 
		 $cond = "sess_id='".self::$sid."' and sess_key='$sessName'";
		 self::delete($name);
		 self::$sessDriver->insert(self::$dbname,$data);
	}
	
	
	public static function get($name){
		
		$sessName = md5($name);

		$data = query::factory()->select('*')->from(self::$dbname)->whereEq(array(
			array('session_key',$sessName),
			array('session_id',self::$sid),
		))->whereMax(array(
			array('sess_expire',time()),
		))->limit(1);
		
		$data = arrayObj::getItem($data,0);
		if($data['sess_getdel'] == 1){
			self::delete($name);
		}
		
		return @unserialize( $data['sess_val'] );
		
	}
	
	public static function delete($name){
		
		$sessName = md5($name);
		$cond = "sess_id='".self::$sid."' and sess_key='$sessName'";
		self::$sessDriver->deleteRecord(self::$dbname,$cond);
		session_unregister($name);
		
		return true;
	}
	
	public static function read($id){

		$sql = "select* from ".self::$dbname. " where sess_id='".self::$sid."' and sess_expire>=".time();
		self::$sessDriver->getResArray(
			$sql,
			false,
			array(sessionDb,'readCallBack')
		);
		
				
		return false;
	}
	
	public static function readCallBack($data){
		$_SESSION[$data['sess_realkey']] = unserialize($data['sess_val']);
	  	return $data;
	}
	
	public static function write($id,$data){
		return false;
	}
	

	public static function destroy($id)
	{
		return false;  
	}
	
	public static function gc($maxlifetime=0){
		
		self::$sessDriver->update(
			self::$dbname,
			array('sess_expire'=>'sess_time+'.time()),
			"sess_id='".self::$sid."' and sess_expire>=".time(),
			false
		);
		
		self::$sessDriver->deleteRecord(
			self::$dbname,
			"sess_id='".self::$sid."' and sess_expire<".time()
		);
		
		return true;
		  
	}
	
	
	
	public static function close()
	{
	  return true;
	}

	
	
}