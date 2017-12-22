<?php
if(!defined('IN_WEB')){
	exit;
}

abstract class EventAction extends Model{
	
	/**
	 * 常量状态开启
	 */
	const STATUS_ON  = 1;
	
	
	/**
	 * 常量状态关闭
	 */
	const STATUS_OFF = 0;
	
	/**排序值*/
	protected $order = 0;
	
	/**状态值*/
	protected $status = 1;
	
	/**
	 * 事件的执行器
	 * @var event
	 */
	protected $executor;
	
	
	/**是否为同步事件*/
	protected $sync = true;
	
	
	/**
	 * 构造
	 * @param event $executor
	 */
	public function __construct(Event $executor){
		$this->executor = $executor;
	}
	
	
	/**
	 * 更新当前执行对象
	 * @param Event $class
	 */
	public function setExecutor(Event $class){
	    $this->executor = $class;
	}
	
	
	/**
	 * 获取排序
	 */
	public function getOrder(){
		return $this->order;
	}
	
	
	/**
	 * 获取状态
	 */
	public function getStatus(){
		return $this->status;
	}
	
	/**
	 * 获取事件行为的名称
	 */
	public function getName(){
		return $this->executor->name.'.'.strtolower( substr(get_class($this),0,6) );
	}
	
	
	/**
	 * 获取事件是否为同步
	 * @return true/false
	 */
	public function sync(){
		return $this->sync;
	}
	
	/**
	 * 执行事件
	 */
	abstract public  function run();
	
	
}