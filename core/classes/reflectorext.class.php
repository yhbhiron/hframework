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

	
	
	public function __call($method,$args){
		return call_user_func_array(array($this->reflectCls,$method),$args);
	}
}
?>