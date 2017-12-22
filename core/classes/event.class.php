<?php
if(!defined('IN_WEB')){
	exit;
}
/**
 * 事件处理器
 * @author yhb
 * @version 2.0.1
 * @since 2016
 */
class Event extends Model{
	
	
	/**事件使用的参数**/
	protected  $params;
	
	
	/**事件的名称*/
	public $name;
	
	
	/**事件执行的结果集*/
	protected $eventResult;
	
	
	/**事件所在的目录,由配置获取**/
	protected $eventPath = '';
	
	/**已加载事件  array */
	protected  static $loadedEvents;
	
	/**类名前缀*/
	private $classPrefix;
	
	
	public function __construct($name,$params=null){
		
		$this->params = $params;
		$this->name = $name;
		$this->eventPath = website::$config['event_path'].'/'.str_replace('.','/',$this->name).'/';
		$this->classPrefix = StrObj::getClassName($this->name);
	}
	
	
	/**
	 * 手动注册一个全局事件驱动
	 * @param string name 事件名称
	 * @param eventAction $action 行为名称
	 */
	public static function register($name,eventAction $action){
		
		if($action->getStatus() == $action::STATUS_ON){
			self::$loadedEvents[$name][] = array('event'=>$action,'order'=>$action->getOrder());
		}
		
		return $this;
	}
	
	
	
	protected function getEventList(){
		
		if(!is_dir($this->eventPath)){
			return;
		}
		
		if(isset(self::$loadedEvents[$this->name])){
		    
		    foreach(self::$loadedEvents[$this->name] as $k=>&$event){
		        $event['event']->setExecutor($this);
		    }
		    
			return self::$loadedEvents[$this->name];
		}
		
		$dirs  = scandir($this->eventPath);
		$events = array();
		foreach($dirs as $k=>$dir){
			
			if(preg_match('/([a-z0-9_]+)\.event\.php/i',$dir,$f) && is_file($this->eventPath.'/'.$dir)){
				
				try{
					@include($this->eventPath.$dir);
					$class  = $this->classPrefix.ucfirst($f[1]).'Event';
	
					if(class_exists($class)){
						
						$event  = new $class($this);
						if($event->getStatus() == eventAction::STATUS_ON){
							$events[] = array('event'=>$event,'order'=>$event->getOrder());
						}
					}
				}catch(Exception $e){
				}
			}
			
			
		}
		

		/**降顺排**/
		$events!=null && usort($events,	function($ev1,$ev2){
			
			if($ev1['order']<$ev2['order']){
				return 1;
			}else if($ev1['order']>$ev2['order']){
				return -1;
			}
			
			return 0;
		});
		
		
		self::$loadedEvents[$this->name] = $events;
		
		return $events;
		
	}
	
	
	/**
	 * 获取事件的相关参数
	 */
	public function getParams(){
		return $this->params;
	}
	
	
	
	/**
	 * 获取最后一次执行的结果集
	 */
	public function getLastResult(){
		return $this->eventResult;
	}
	
	
	
	
	/**
	 * 把事件放入队列中执行
	 * @param eventAction $class
	 */
	public function toAsyncQueue($class){
		
		Queue::factory()->enter(
			'
				$actStr = \''.unserialize($class).'\';
				$act = @unserialzie($actStr);
				$act->run();
			'
		);
		
	}
	
	/**
	 * 执行事件
	 */
	public function run(){
		
		$t = website::curRunTime();
		$events = $this->getEventList();
		website::debugAdd('监听事件'.$this->name,$t);
		if($events == null){
			return;
		}
		
		foreach($events as $k=>$e)
		{
			$class = $e['event'];
			if($class->sync() == true){
				$this->eventResult[$class->getName()] = $class->run();
			}else{
				$this->toAsyncQueue($class);
			}	
		}
		
		website::debugAdd('执行事件'.$this->name,$t);
		return $this->eventResult;
	}
		
}
?>