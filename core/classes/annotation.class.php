<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 注解功能@@>之前执行,@@<之后执行
 * 方法的注解：一种只是执行一个过程，一种是影响方法体是否继续执行，一种是对方法的结果造成影响
 * 一种是对方法执行前和执行后，都有影响的注解
 * 相关注解的类放在annotation目录下 并且继承至annotationBase，实现execute方法
 * @author Jack Hiron
 * @version 1.0
 * @since 2017-01-24
 */
class Annotation extends model{
	
    /**
     * 实例化的对象
     * 
     */
	protected $cls;
	
	
	/**
	 * 代理类
	 * @var reflectorExt
	 */
	protected $refCls;
	
	/**在之前执行*/
	const ANN_EXE_BEFORE = 1;
	
	/**在之后执行*/
	const ANN_EXE_AFTER  = 2;
	
	/**
	 * @param String $class 类名
	 */
    public function __construct($class){

    	$params = array_slice(func_get_args(),1);
    	$this->refCls =  new reflectorExt($class) ;
        $list = $this->getAnn($this->refCls->getClassComment());
        $this->exeAnn($list, self::ANN_EXE_BEFORE,$params);    	
		$this->cls = $this->refCls->newInstanceArgs($params);
		if($this->existsProperty('refObject')){
			$this->cls->refObject = $this;
		}

   }
    

   /**
    * 执行注解的相关内容
    * @param array $annList 注解列表
    * @param int $type ANN_EXE_BEFORE ANN_EXE_AFTER
    * @return array
    */
   public function exeAnn($annList,$type){
   		
	   	if($annList == null){
	   		return false;
	   	}
	   	
	   	$typeAnns = array_filter($annList,function($v)use($type){ return $v['exetype'] == $type;} );
	   	if($typeAnns == null){
	   		return false;
	   	}
	   	
	   	$results = array();
	   	$lastResult = null;
	   	foreach($typeAnns as $name=>$ann){
	   		$class = 'annotation'.ucfirst($name);
	   		if(class_exists($class,true)){
	   			$a = new $class($ann);
	   			$params = array_slice(func_get_args(),2);
	   			$params['last_result'] = $lastResult;
	   			$lastResult = $a->execute($params);
	   			$results[] = $a->execute($params);
	   		}else{
	   			$this->error('不存在的注解:'.$name);
	   		}
	   	}
	   	
	   	return $results;
	   	
   }
   

   /**
    * 获取注解列表
    * @param string $comment 注释内容
    * @param string $method 相关方法
    */
    protected function getAnn($comment,$method=null){
    	
    	if($comment=='' || !preg_match_all('/\*\s*@@(<|>)([a-z0-9]+)\s*(\((.*?)\))?(\n|\r)/i',$comment,$list)){
    		return array();
    	}
    	
    	if(!isset($list[2]) || arrayObj::trim($list[2]) == null){
    		return array();
    	}
    	
    	$annList = array();
    	foreach($list[2] as $k=>$name){
    		
    		$name = trim($name);
    		$annList[$name] = array(
    			'class'=>$this->cls,
    			'refclass'=>$this->refCls,
    			'method'=>$method,
    			'exetype'=> $list[1][$k] == '>' ? self::ANN_EXE_BEFORE : self::ANN_EXE_AFTER,
    			'params'=>array(),
    		);
    		
    		if(isset($list[4][$k]) && validate::isNotEmpty($list[4][$k]) ){
    			
    			if(preg_match_all('/(([a-z0-9_]+?)\s*=\s*(.+?,|.+))+?/',$list[4][$k],$params)){
    				
    				foreach($params[2] as $j=>$p){
    					$annList[$name]['params'][trim($p)] = trim( trim(arrayObj::getItem($params[3],$j),',') );
    				}
    			}
    		}
    		
    	}
    	
    	return $annList;
    	
    }
    
    public function __call($method,$args){
        
    	/**
    	 * 前置函数
    	 */
    	$beforeMethod = 'before'.$method;
    	if($this->existsMethod($beforeMethod)){
    			
    		$result = $this->invokeMethod($beforeMethod, $args);
		    if(arrayObj::getItem($result,'break') == true){
		    	return arrayObj::getItem($result,'return_list');
		    }
    			
    		$args[] = $result;
    	}
        
        /**
         * 执行方法前置的注解方法，如果有break,则中止方法继续执行,并返回return_list,如果未中止，读取他想执行的后置函数after_func
         */
    	$list = array();
    	$this->existsMethod($method) && $list = $this->getAnn($this->refCls->getMethodComment($method));
    	if($list != null){
    	    
	    	$beforeResult = $this->exeAnn($list, self::ANN_EXE_BEFORE,$method,$args); 
	    	if(arrayObj::getItem($beforeResult,'break') == true){
	    		return arrayObj::getItem($beforeResult,'return_list');
	    	}
	    	
	    	$afterFunc = arrayObj::getItem($beforeResult,'after_func');
	    	$args[] = $beforeResult;
    	}
    	
    	
    	/**执行函数主体*/
    	$result      = call_user_func_array(array($this->cls,$method),$args);
    	
    	/**至后函数注解如果有一个return_list 则，函数会的结果会被影响**/
    	$afterResult = method_exists($this->cls,$method) &&  $this->exeAnn($list, self::ANN_EXE_AFTER,$result,$method,$args); 
    	if(isset($afterResult['return_list'])){
    		$result = $afterResult;
    	}
    	
    	if(isset($afterFunc) && is_callable($afterFunc)){
    	    $afterFunc($result);
    	}
    	
    	
    	/**
    	 * 后置函数
    	 */
    	$afterMethod = 'after'.$method;
    	if($this->existsMethod($beforeMethod)){
    	         
    	    $args[] = $result;
    	    $afterResult = $this->invokeMethod($afterMethod, $args);
    	    if(arrayObj::getItem($afterResult,'replace')!=null){
    	    	$result = $afterResult['replace'];
    	    }
    	        
    	}
    	
    	return $result;
    }
    
    
    /**
     * 强制执行类中的一个方法,不管是不是Public
     * @param string $method 方法名
     * @param array $args 参数列表
     * @return mixed
     */
    public function invokeMethod($method,$args){
    	
    	$func = new ReflectionMethod($this->cls,$method);
    	$func->setAccessible(true);
    	$result = $func->invokeArgs($this->cls,$args);
    	
    	return $result;
    }
    
    
    
    /**
     * 检查类的方法是否存在
     * @param string $method
     * @return boolean
     */
    public function existsMethod($method){
        return method_exists($this->cls, $method);
    }
    
    
    /**
     * 检查类的属性是否存在
     * @param string $name
     * @return boolean
     */
    public function existsProperty($name){
        return property_exists($this->cls, $name);
    }
    
    
    public function __get($var){
    	return $this->cls->{$var};
    }
    
    
    public function __set($var,$val){
    	$this->cls->{$var} = $val;
    }
    
    
    
	/**
	异常报告
	@param string $exception 异常
	*/    
    public function error($msg){
    	website::error($msg,2,4,10);
    }

}