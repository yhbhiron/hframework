<?php
/**
静态化生成入口类
需要配合相关配置文件,引用类：staticRule
@version 1.0.0
@author jackbrown;
@time 2013-07-31
**/

if(!defined('IN_WEB')){
 	exit;
}

class htmlStatic{
	
	/**调用处理程序路径**/
	protected $callbackDir = '';
	
	/**配置数据**/
	protected $cnfData = array();
	
	

	public function __construct(){
		
		/**检测配置的合法性**/
		$rule = website::loadConfig('staticRule',false);
		if($rule == null){
			$this->error('没有配置完整的配置!',2);
			return false;
		}
		
		$this->callbackDir = website::$config['static_callback_dir'];
		if(!is_dir($this->callbackDir)){
			$this->error('静态化处理库路径不存在！',2);
			return false;
		}
		
		$this->cnfData = $rule;
		website::loadClass('staticCallBack');
		set_time_limit(0);
	}
	
	
	/**
	 * 保存静态文件
	 * @param string $cnfName配置文件名称
	 */
	public function saveHTML($cnfName){
		
		if($this->cnfData[$cnfName] == null){
			return false;
		}
		
		$cnf = $this->cnfData[$cnfName];
		if($cnf['callback']==null){
			
			$this->error('配置缺少callback项!',1);
			return false;
			
		}
		
		$this->exeCallBack($cnf);
		
	}
	
	/**
	 * 执行回调函数
	 * @param unknown_type $cnf
	 * @param mixed $param提供回调函数的参数 
	 */
	protected function exeCallBack($cnf,$param=null){
		
		$callName = $cnf['callback'];
		$callFile = $this->callbackDir.'static.'.$callName.'.callback.php';
		if(!is_file($callFile)){
			
			$this->error('静态化处理函数不存在！',2);
			return false;
			
		}
		
		try{
			include($callFile);
		}catch(Exception $e){
			throw($e);
			$this->error('静态化函数错误',2);			
		}
		
		$funcName = 'staticCall'.$callName;
		if(!function_exists($funcName)){
			
			$this->error('静态化处理函数'.$funcName.'不存在！',2);
			return false;
			
		}
		
		$funcName($cnf,$param);
		
	}
	
	/**
	 * 显示操作消息
	 * @param string  $msg 错误消息
	 * @param int $level  错误级别
	 */
	private function error($e,$level){
		$e.= ';Class:'.get_class($this);	
		website::error($e,$level,3);
	}
	
	
}



?>