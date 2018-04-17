<?php
/**
 * redis 操作
 */
class cacheRedis{
	
	/**服务器连接信息**/
	protected static $servers = array();
	
	/**redis是否安装可用**/
	protected $enable = true;
	
	/**配置信息**/
	protected static $config = null;
	
	public function __construct(){
		
		
		if(self::$servers == null){
		    
		    if(!class_exists('redis')){
		        throw new Exception('没有安装redis扩展');
		        $this->enable = false;
		        return false;
		        
		    }
			
			self::$config = website::loadConfig('cache.redis',false);
			if(self::$config == null){
				$this->enable = false;
			}else{
				
				self::$servers['master'] = new redis();
				self::$servers['master']->connect(self::$config['master']['host'],self::$config['master']['port']);
				if(validate::isNotEmpty(arrayObj::getItem(self::$config['master'], 'auth'))){
					self::$servers['master']->auth(self::$config['master']['auth']);
				}
				
			}
			
		}
	}
	
	public function write($key,$data,$expire=0){
		
		if($key == null || $data == null || $this->enable == false){
			return false;
		}
		
		website::debugAdd('redis写入'.$key);
		if($expire>0){
		  return self::$servers['master']->setex($key,$expire,serialize($data));
	    }else{
	        return self::$servers['master']->set($key,serialize($data));
	    }
	}
	
	public function read($key){
		
		if($this->enable == false){
			return false;
		}
		
		website::debugAdd('redis读取'.$key);
		$svr = $this->getSlaves();
		return @unserialize($svr->get($key));
		
	}
	
	
	public function delete($key){
		
		if($this->enable == false){
			return false;
		}
		
		return self::$servers['master']->delete($key);
		
	}
	
	
	/**
	 * 获取从服务器
	 */
	protected function getSlaves(){
		
		if($this->enable == false){
			return false;
		}
		
		if(self::$config['slaves'] == null){
			return self::$servers['master'];
		}
		
			
		$skey   = array_rand(self::$config['slaves']);
		$config = self::$config['slaves'][$skey];
		
		if(self::$servers['slaves'][$skey] == null){
			
			$svr = new redis();
			$svr->connect($config['host'],$config['port']);
			return $svr;
		}
		
		return self::$servers['slaves'][$skey];
			
		
	}
	
	
	public function __call($m,$args){
		
		if($this->enable == false){
			return false;
		}
		
		return call_user_func_array(array(self::$servers['master'],$m),$args);
	}
	
}