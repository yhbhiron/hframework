<?php
/**
*缓存类
*@version 1.0.0
*@author jackbrown;
*@since 2013-08-02
*@example
*$c = new superCache();
//$sql = "SELECT * FROM `guu_prodcat`";
//print_r(dataBase::instance()->getSQLTables($sql));
$a = array('name'=>'yhb','age'=>18);
//$c->write('info',$a,60);
print_r($c->read('info'));
//$c->listen();
//$c->clear($sql);
**/
abstract class SuperCache extends Model{
	
	protected  $config;
	
	/**
	 * 缓存键名
	 */
	protected $key;
	
	/**
	 * 实例化一个缓存对像
	 * @param string  $key 键名
	 * @throws Exception
	 * @return superCache
	 */
	public static function factory($key){
		
		$config = website::loadConfig('cache',false);
		if(!isset($config[$key])){
			throw new exceptionCache('不存在的缓存配置',12);
		}
		
		$driver = 'cache'.ucfirst(arrayObj::getItem($config[$key], 'type'));
		if(!class_exists($driver)){
			throw new exceptionCache('不存在的缓存类型',14);
		}
		
		return new $driver($key,$config[$key]);
	}
	
	
	/**
	 * 构造函数 
	 * @param string $key 缓存键名
	 * @param array $config 缓存的配置项
	 */
	public function  __construct($key,$config){
		$this->key = $key;
		$this->config = $config;
	}
	
	/**
	 * 从缓存区读取数据
	 * @param $key 缓存键值
	 * @return mixed 
	 */
	abstract public function read();
	
	
	/**
	 * 写入数据到缓冲出，并返回写入的数据
	 * @param string $key 缓存键值
	 * @param string $data 写入数据,可选，如果不指定值会自动读取指定数据源的数据
	 * @return 返回写入的数据,失败返回false
	 */
	abstract public function write($data,$expire);
 
	
	
	/**
	 * 清除所
	 * @param string  $key
	 */
	abstract public function delete();
	
	
	/**
	 * 是否变更
	 * @param string $key
	 */
	abstract public function isChanged();
	
	
	/**
	 异常报告
	 @param string $exception 异常
	 */
	public function error($msg){
		website::error($msg,2,4,10);
	}
	
}