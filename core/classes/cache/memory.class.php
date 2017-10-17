<?php
/**
 * Memcache内存缓存
 *
 */
class cacheMemory{
	
	/**memcache对象**/
	protected $memObj = null;
	
	/**缓存的hash表**/
	public $hash = null;
	
	/**memcache是否已经启用**/
	protected $enable = true;
	
	/**服务器连接池**/
	protected static $connect= null;
	
	/**配置信息**/
	protected $config = null;
	
	public function __construct(){
		
		$driverName = '';
		if(class_exists('Memcache')){
			$driverName = 'Memcache';
		}else if(class_exists('Memcached')){
			$driverName = 'Memcached';
		}else{
			
			$this->enable = false;
			return false;
			
		}
		
		if(self::$connect == null){
			
			$this->config = website::loadConfig('memory',false,'cache/');
			if($this->config == null){
				
				$this->enable = false;
				
			}else{
				
				self::$connect = new $driverName();
				foreach($this->config['hosts'] as $k=>$svr){
					self::$connect->addServer($svr['host'],$svr['port']);
				}
				
			}
		}
		
	}
	
	/**
	 * 读取缓存
	 * @param string $key 键值
	 * @return mixed 缓存数据
	 */
	public function read($key){
		
		if($this->enable == false){
			return null;
		}
		
		return self::$connect->get($key);
	}
	
	
	/**
	 * 写入到内存缓存
	 * @param string $key  键值
	 * @param mixed $data 需要缓存的数据
	 */
	public function write($key,$data,$expire){
		
		if($key == null || $data==null || $this->enable == false){
			return;
		}
		
		return self::$connect->set($key,$data,0,$expire);
	}
	
	
	/**
	 * 删除缓存
	 * @param string $key
	 */
	public function delete($key){
		
		if($this->enable == false){
			return;
		}
		
		self::$connect->delete($key);
	}
	
	/**
	 * 遍历所有，并应用于一个回调函数
	 */
	public function explore($callback=null){
		
		$items = self::$connect->getExtendedStats ('items');	
        $first = arrayObj::getItem($this->config['hosts'],0);
		$items= arrayObj::getItem($items["{$first['host']}:{$first['port']}"],'items');
		
		if($items == null){
			return;
		}

        foreach($items as $key=>$values){

             $number=$key;;
	         $str=self::$connect->getExtendedStats ("cachedump",$number,0);
	         $line=$str["{$first['host']}:{$first['port']}"];
	
	         if( is_array($line) && count($line)>0){
	
	             foreach($line as $key=>$value){
	             	
	             	if(is_callable($callback)){
	                 	call_user_func($callback,$key);
	             	}else{
	             		break;
	             	}
	             	
	            }
	
	        }

     	}	
	}
	
	/**
	 * 显示文件操作消息
	 * @param string  $msg 错误消息
	 * @param int $level  错误级别
	 */
	private function error($e,$level){
		$e.= ';Class:'.get_class($this);	
		website::error($e,$level,3);
	}
}