<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 队列
 * @author yhb
 * @since 2016.07.22
 * @version 1.0.0
 */
abstract class queue extends  model{
	
	public static $default = 'db';
	
	/**
	 * @return queue
	 * @param string $name 队列类型
	 */
	public static function factory($name=null){
		
		$name = StrObj::def($name,self::$default);
		$className = 'queue'.ucfirst($name);
		return new $className();
	}
	
	/**
	 * 入队
	 * @param string $code　执行的代码
	 */
	abstract public function enter($code);
	
	
	/**
	 * 执行队列并出队
	 * @param int $queue 队列的id
	 */
	abstract  public  function execute($queue);
	
	
	/**
	 * 监听
	 */
	abstract public  function listen();
		
	
}