<?php
if(!defined('IN_WEB')){
	exit;
}
class Model{
	
	/**
	 * 注解对象,如果要使用aop相关的
	 * 功能，请使用此类调用相关类的方法
	 * @var Annotation
	 */
	public  $annObject;
	
	
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
		
		return ArrayObj::getItem(self::$_system,$name);
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
		
		return ArrayObj::getItem(self::$_system,$name);
	}
	
	
	/**
	 * 获取当前对象的注解实例
	 * @return Annotation
	 */
	public function getAnnObject(){
	    
	    if(isset($this->annObject)){
	        return $this->annObject;
	    }
	    
	    $this->annObject = $object = new Annotation(get_class($this));
	    return $this->annObject;
	}
	
	
	/**
	 *  获取当前对象是否存在属性
	 * @param string $name 属性名称
	 * @return boolean
	 */
	public function existsAttr($name){
	    
	    if($this instanceof  Annotation){
	        return $this->existsProperty($name);
	    }else{
	        return property_exists($this, $name);
	    }
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
	
	/**
	 * 格式化数据格式
	 * @param mixed $data
	 * @return mixed
	 */
	public static function dataFormat($data,$format=array(),$name=''){
	    
	    
	    if(is_array($data)){
	        foreach($data as $k=>$v){
	            $data[$k] = self::dataFormat($v,$format,$k);
	        }
	    }else{
	        
	        $func = ArrayObj::getItem($format,$name);
	        if(is_callable($func)){
	            $data = call_user_func($func,$data);
	        }else if(method_exists('StrObj',$func)){
	            $data = StrObj::$func($data);
	        }else if(method_exists('Number',$func)){
	            $data = number::$func($data);
	        }else if(method_exists('ArrayObj',$func)){
	            $data = arrayObj::$func($data);
	        }else{
	            
	            if(is_numeric($data)){
	                
	                if(strstr($data,'.')){
	                    $data = floatval($data);
	                }else{
	                    $data = intval($data);
	                }
	                
	            }else if(is_bool($data)){
	                $data = $data == true ? 1 : 0;
	            }else if(is_null($data) || strval($data) == null){
	                $data = '';
	            }
	            
	        }
	        
	    }
	    
	    
	    return $data;
	    
	}
	
	
}