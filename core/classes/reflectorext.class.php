<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 反射扩展操作
 * @desc 不存在的方法则调用反射类的方法
 * @author Administrator
 */
class ReflectorExt extends Model {
	
	/**
	 * 代理反射 类
	 * @var ReflectionClass
	 */
	protected $reflectCls;
	
	
	/**
	 * 保存获取的常量列表
	 * @var array
	 */
	protected static $constList = array();
	
	
	/**
	 * @param string $cls 反射类名
	 */
	public function __construct($cls){
		$this->reflectCls= new ReflectionClass($cls);
	}
	
	
	/**
	 * 获取类的注释
	 * @return string
	 */
	public function getClassComment(){
		return $this->reflectCls->getDocComment();
	}
	
	
	/**
	 * 获取类的某方法的注释
	 * @param string $method 方法名称
	 * @return string 
	 */
	public function getMethodComment($method){
		return $this->reflectCls->getMethod($method)->getDocComment();
	}
	
	
	
	/**
	 * 获取类相关所有方法的注释
	 * @return array
	 */
	public function getMethodsCommentList(){
		
		$list = $this->reflectCls->getMethods();
		if($list == null){
			return array();
		}
		
		$comments = array();
		foreach($list as $k=>$method){
			$comments[$method->getName()] = $method->getDocComment();
		}
		
		return $comments;
	}
	
	
	/**
	 * 获取属性的注释
	 * @param string $member 属性名称
	 * @return string
	 */
	public function getMemberComment($member){
		return $this->reflectCls->getProperty($member)->getDocComment();
	}
	
	
	
	/**
	 * 获取所有类的属性的注释列表
	 * @return array  
	 */
	public function getMembersCommentList(){
		
		$list = $this->reflectCls->getProperties();
		if($list == null){
			return array();
		}
		
		$comments = array();
		foreach($list as $k=>$member){
			$comments[$member->getName()] = $member->getDocComment();
		}
		
		return $comments;
	}
	
	/**
	 * 获取类的常量注释列表
	 * @return array(array('value'=>值,'comment'=>注释))
	 */
	public function getConstCommentList(){
	    
	    $file =  $this->reflectCls->getFileName();
	    $constKey = md5($file);
	    if(isset(self::$constList[$constKey])){
	        return self::$constList[$constKey];
	    }
	    
	    if(PHP_VERSION >=7){
    	    $list = $this->reflectCls->getConstants();
    	    if($list == null){
    	        return array();
    	    }
    	    
    	    $comments = array();
    	    foreach($list as $name=>$value){
    	        
    	        $const = new ReflectionClassConstant($this->reflectCls->getName(),$name);
    	        $comments[$name] = array(
    	            'comment'=>trim(preg_replace('/\/|\*/','',$const->getDocComment())),
    	            'value'=>$value,
    	        );
    	    }
	    }else{
	        
	        
	        $comments = array();
	        $list = Filer::readFileLine($file,function($line,$list,$lineNum)use(&$comments){
	            
	            $line = trim($line);
	            $list[$lineNum] = $line;
	            if(preg_match('/^const\s([^=]+)/i',$line,$m)){
	                
	                $name = trim($m[1]);
	                $start = $lineNum-1;
	                $commentStr = '';
	                $isComment = true;
	                
	                while($isComment){
	                    
	                    $startStr = ArrayObj::getItem($list,$start);
	                    $isComment = preg_match('/\/\*+/',$startStr) || preg_match('/^\*+/',$startStr);
	                    if(!$isComment){
	                        break;
	                    }
	                    
	                    $commentStr.=$startStr;
	                    $start--;
	                }
	                
	                $comments[$name] = $commentStr;
	            }
	            
	            return $list;
	        });
	        
	         if($comments!=null){
	             foreach($comments as $name=>$comment){
	                 $comments[$name] = array(
	                     'value'=>$this->reflectCls->getConstant($name),
	                     'comment'=>preg_replace('/\/|\*/','',$comment),
	                 );
	             }
	         }
	        
	    }

	    self::$constList[$constKey] = $comments;
	    return $comments;
	    
	}
	
	/**
	 * 获取某类常量的注释
	 * @param string $prefix 常量前缀或常量名称
	 * @param mixed $value 常量对应的值 
	 * @return string/array
	 */
	public function getConstComment($prefix,$value=null){
	    
	    $consts = $this->getConstCommentList();
	    if($consts!=null){
	        
	        $preLen = strlen($prefix);
	        foreach($consts as $name=>$const){
	            
	            if(StrObj::left($name,$preLen) == $prefix && $const['value'] == strval($value)){
	                return $const['comment'];
	            }
	            
	            if(StrObj::left($name,$preLen) != $prefix){
	                unset($consts[$name]);
	            }
	            
	        }
	        
	        if(Validate::isNotEmpty($value) == true){
	            return null;
	        }
	        
	        
	        return ArrayObj::toHashArray($consts, 'value', 'comment');
	    }
	    
	    return null;
	    
	}
	
	
	
	public function __call($method,$args){
		return call_user_func_array(array($this->reflectCls,$method),$args);
	}
}
?>