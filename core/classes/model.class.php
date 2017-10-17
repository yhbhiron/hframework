<?php
if(!defined('IN_WEB')){
	exit;
}
class model{
	
	/**
	 * 代理类的对象,如果要使用aop相关的
	 * 功能，请使用此类调用相关类的方法
	 * @var reflectorExt
	 */
	public  $refObject;
	
	
	/**
	 * 指定所属模块的列表
	 * @var array
	 */
	public static $_system = array();
	
	
	
	public function __set($vars,$val){
		
		if(property_exists($this, $vars)){
			throw new Exception('修改Protected属性：'.$vars);
		}		
		
		throw new Exception(get_class($this).";属性: $vars 不存在",2);
		return false;
	}
	
	
	public function __get($vars){
		
		if(property_exists($this, $vars)){
			throw new Exception('调用Protected属性：'.$vars);
		}
		
		throw new Exception(get_class($this).";属性: $vars 不存在",2);
		return NULL;
	}
	
	
	public function __call($method,$params){
		
		if(method_exists($this, $method)){
			throw new Exception('调用Protected方法：'.$method);
		}
		
		throw new Exception(get_class($this).";方法:$method 不存在",2);
		return NULL;
	}
	
	
	/**
	 * 获取当前对象所属的系统
	 * @return system
	 */
	protected function system(){
		
		$name  = strtolower(get_class($this));
		$pname = strtolower(get_parent_class($this));
		if($pname!='' && isset(self::$_system[$pname])){
			return self::$_system[$pname];
		}
		
		return arrayObj::getItem(self::$_system,$name);
	}

	
	/**
	 * 获取当前对象所属的系统
	 * @return system
	 */
	protected static function _system(){
		
		$name  = strtolower(get_called_class());
		$pname = strtolower(get_parent_class());
		
		if($pname!='' && isset(self::$_system[$pname])){
			return self::$_system[$pname];
		}
		
		return arrayObj::getItem(self::$_system,$name);
	}
	
	
	
	/**
	 * 检查方法输入参数
	 * @param array $params 参数列表array(0=>val0,1=>val1)
	 * @param array $types 类型列表 array('int','string','float','isInt'=>array(0))
	 * @param int $num 最多参数个数
	 * @throws Exception
	 */
	public static function checkParamsType($params,$types,$num){
		
		if(validate::isNotEmpty($params,true) == false || validate::isNotEmpty($types,true) == false ){
			return false;
		}
		
		$passedNum = count($params);
		if($passedNum >$num){
		    throw new Exception('参数过多，最多只有'.$num.'个');
		}

		foreach($params as $k=>$value){
			
			$type = current($types);
			$key  = key($types);
			if($types == null){
				break;
			}
			
			if(is_callable($key) && is_array($type)){
				
				$p = array_unshift($type, $value);
				$passed = call_user_func_array($key,$p);
				
			}else if(is_callable($type)){
				$passed = call_user_func($type,$value);
			}else if(method_exists('validate', $key)  && is_array($type)){
				
				$p = array_unshift($type, $value);
				$passed = call_user_func_array(array('validate',$key),$p);
				
			}else if(method_exists('validate', $type)){
				$passed = call_user_func(array('validate',$type),$value);
			}
			
			if($passed == false){
				throw new Exception('参数错类型错误：'.$k);
			}
			
			next($types);
		}
		
		
	}
	
	
}