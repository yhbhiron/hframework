<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 注解的相关实现基类函数
 * @author yhb
 */
abstract class AnnotationBase extends Model {
	
	/**
	 * 源类
	 */
	protected $sourceCls;
	
	
	/**
	 * 源反射类
	 * @var 
	 */
	protected $sourceRefCls;
	
	
	protected $theMethod;
	
	
	/**
	 * 
	 * @param array $annParams('class'=>原对象实例,'refclass'=>原对像反射类,'method'=>'相关方法',params=>'相关属性列表')
	 */
	function __construct($annParams) {
		
		$this->sourceCls    = $annParams['class'];
		$this->sourceRefCls = $annParams['refclass'];
		$this->theMethod    = $annParams['method'];
		
		if(arrayObj::getItem($annParams,'params')!=null){
			foreach($annParams['params'] as $name=>$value){
				$setMethod = 'set'.ucfirst($name);
				if(!method_exists($this, $setMethod) || !property_exists($this,$name)){
					$this->error('不存在的属性:'.$name);
				}
			}
		}
		
		
	}
	
	/**
	 * 执行注解内容
	 * @return  返回结果可以包括：break 是否中止下一步操作true/false,return_list返回的结果
	 */
	abstract public function execute();
	
	
    protected function error($msg){
    	website::error(get_class($this).':'.$msg,2,4,10);
    }	
}