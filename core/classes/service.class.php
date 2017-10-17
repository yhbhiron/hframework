<?php !defined('IN_WEB')  && exit('Not Allowed');
/**
 * 服务基类,业务相关的操作，
 * 默认使用与自己同名的orm来操作
 * @author yhb
 * @since 2016-05-26
 */
abstract class service extends model{
	
	/**模型名称*/
	protected $name = '';
	
	/**模型的值*/
	protected $modVal = '';
	
	/**加载的默认模块*/
	protected static $loadedDefaultMod = array();
	
	
	public function __construct(){
		$this->name = $this->name!='' ? $this->name : substr(get_class($this),0,-7);
	}
	
	
	/**
	 * 获取内部模型
	 * @return ORM
	 */
	public function model($v=null,$refresh=false){
		
		if($v ==null || $refresh || !isset(self::$loadedDefaultMod[$v])){
			
			$this->modVal = $v;
			$model =  $this->system()->model($this->name,$v);
			if($v !=null){
				self::$loadedDefaultMod[$v] = $model;
			}
			
			return $model;
		}
		
		return  self::$loadedDefaultMod[$v];
	}
	
	/**
	 * 没有定义的方法默认调用内置orm
	 * @see model::__call()
	 */
	public function __call($method,$args){
		
		if(method_exists($this, $method)){
			throw new Exception('调用Protected方法：'.$method);
		}
		
		$model = $this->model($this->modVal);
		return call_user_func_array(array($model,$method),$args);
	}
	
	
	public function __get($attr){
		return $this->model($this->modVal)->$attr;
	}
	
}
