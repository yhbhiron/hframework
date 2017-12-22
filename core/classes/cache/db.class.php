<?php
/**
 * 数据库缓存，必须要dbcon的配置中指定cache的连接信息,包括表
 * cache_index
 * cache_noraml
 * @name 数据库缓存
 * @author Jack Hiron
 *
 */
class CacheDb extends superCache{
	
	/**配置**/
	protected $cacheConfig = array();
	
	
	const CACHE_NORMAL = 1;
	const CACHE_SQL    = 2;

	public function __construct($key,$config){
	    
	    parent::__construct($key,$config);
		$this->cacheConfig = website::loadConfig('cache.dbCache',false);
			
	}
	
	/**
	 * 写入缓存
	 * @param string $key缓存键名
	 * @param minded $data写入的数据，可为sql或数组，数组格式：array('table'=>源表,'datas'=>数据项);
	 */
	public function write($data=null,$expire=0){
		
		$key = $this->key;
		$hash = md5($key);
		
		$index    = $this->getCacheIndex($hash);
		
		$type = $this->getCacheType($key);
		$item = array();
		$item['key_type'] = $type;
		$item['key_name'] = $hash;
		$item['key_real'] = $key;
		$item['key_expire'] = $expire>0 ? $expire+time() : 0;
		$item['key_uptime'] = time();
  		
		$indexInfo = $index->get('*');
		
		if($indexInfo == null){
			$index->add($item);
		}else{
			if($type != $indexInfo['key_type']){
				
				if($indexInfo['key_type'] == self::CACHE_NORMAL){
					$normal = new db('cache_normal','key_name',$hash);
					$normal->remove();
				}else{
					$this->query('drop table '.$hash);
				}
			}
			
			$index->set($item);
		}
		
		website::debugAdd('写入数据库缓存'.$key);
		if($type == self::CACHE_NORMAL){
			
			$normal = new cacheDb('cache_normal','key_name',$hash);
			$cacheData = array();
			$cacheData['key_name']  = $hash;
			$cacheData['key_value'] = serialize($data);
			
			if($normal->get('key_name') == null){
				return $normal->add($cacheData);
			}else{
				return $normal->set($cacheData);
			}	
			
			
			
		}else if($type == self::CACHE_SQL){
			
			if($this->existsTable($hash)){
				$this->query('drop table '.$hash);	
			}
			
			return db::instance(self::$writeDbkey)->copyFromSQL($this->config['database'].'.'.$hash,$key) && $this->opTable($hash);
		}
		
		
	}
	
	
	/**
	 * 读取数据
	 * @param string $key 缓存键名
	 * @param string/array $callbak 对结果的回调函数，主要争对sql查询
	 * @return array 数据值
	 */
	public function read($callback=null){
		
		if($key == ''){
			return null;
		}
		
		$hash = md5($key);
		$index = $this->getCacheIndex($hash);
		$info  = $index->get('*');
		
		if($info == null){
			return null;
		}
		
		$type = $this->getCacheType($key);
		if($info['key_expire']>0 && $info['key_expire']<time()){
			
			$this->delete($key);
			return null;
		}
		
		if($type == self::CACHE_NORMAL){
			
			$normal = new cacheDb('cache_normal','key_name',$hash);
			return @unserialize( $normal->get('key_value') );
			
		}else{
			
			if(!$this->existsTable($hash)){
				return null;
			}
			
			if(!is_callable($callback)){
				$sql = "select*from $hash";
			}else{
				$sql = $callback($hash);
			}
			
			return $this->getResArray($sql);
		}
		
		
			
	}
	
	
	/**
	 * 删除表
	 * @param string $key 键名
	 * **/
	public function delete($key){
		
		$hash = md5($key);
		$index = $this->getCacheIndex($hash);
		$info = $index->get('*');
		
		if($info == null){
			return;
		}
		
		if($info['key_type'] == self::CACHE_NORMAL){
			
			$normal = new cacheDb('cache_normal','key_name',$hash);
			$normal->remove(); 
			$index->remove();
			
		}else{
			
			if($this->existsTable($hash)){
				$this->query('drop table '.$hash);	
			}
			$index->remove();
		}
		
		
	}
	
	/**
	 * 临视缓存所需的数据源是否有更新
	 */
	public function listen(){
		
		$index = $this->getResArray('select*from cache_index');
		if($index == null){
			return;
		}
		
		foreach($index as $k=>$c){
			
			if($c['key_type'] == self::CACHE_SQL){
			
				$tables = db::instance()->getSQLTables($c['key_real']);
				if($tables!=null){
				
					$changed = false;
					foreach($tables as $k=>$tinfo){
						
						$info = $this->getTableInfo($tinfo['table'],$tinfo['db']);
						if( strtotime($info['Update_time']) > $c['key_uptime'] ){
							$changed = true;
							break;
						}
						
					}
					
					if($c['key_expire']>0 && $c['key_expire']<time()){
						$changed = true;
					}
					
					if($changed){
						$this->write($c['key_real'],null,$c['key_expire']);
					}
					
				}
			}else{
			/**eof key_type=sql**/
				
				if($c['key_expire']>0 && $c['key_expire']<time()){
					$this->delete($c['key_real']);
				}
				
			}
			
		}
		
	}
	
	
	
	/**
	 * 获取缓存的类别
	 * @param string $key 键名称
	 */
	protected function getCacheType($key){
		
		if(preg_match('/^select[\s\S]+from/i',$key)){
			return self::CACHE_SQL;
		}else{
			return self::CACHE_NORMAL;
		}
	}
	
	
	protected function getCacheIndex($key){
		
	    $index = ORM::factory('cacheindex',$key);
		return $index;
		
	}
	
}

?>