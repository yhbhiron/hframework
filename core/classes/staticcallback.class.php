<?php
/**
*静态化生回调函数处理类
*@version 1.0.0
*@author jackbrown;
*@time 2013-08-02
**/

if(!defined('IN_WEB')){
 	exit;
}

class staticCallBack{
	
	/**静态化的类别,值page,list**/
	protected $type = ''; 	
	
	/**静态化的名称，用于保存静态化的文件目录**/
	protected $name = '';
	
	/**静态化的页面参数**/
	protected $params = array();
	
	/**页码**/
	protected $pageSum =0;
	
	/**处理静态化脚本名称**/
	protected $baseURL = '';
	
	/**数据库连接变量**/
	protected $dbCon = null;
	
	/**
	 * 构造函数
	 * @param string $name 静态化的名称
	 * @param string $type 静态化的类型
	 * @param string $url 静态化的脚本基础
	 */
	public function __construct($name,$type,$url){

		$this->name    = $name;
		$this->type    = $type;
		$this->baseURL =  $url;
		
		if(!in_array($this->type,array('page','list'))){
			return false;
		}
		
		//$this->dbCon = new dataBase();
		
	}
	
	
	/**
	 * 为脚本增加一列参数
	 * @param string  $paramName 参数名称
	 * @param mixed $value 参数值
	 */
	public function addParam($paramName,$value){
		
		$this->params[$paramName] = $this->getParamVal($paramName,$value);	;
	}
	
	
	/**
	 * 执行处理函数
	 * @param string $callBack 地址组合处理回调函数
	 * @param array $config 相关配置
	 * @param mixed $userData 用户自定义数据
	 */
	public function execute($callBack,$config,$userData=null){
		
		if(!validate::isNotEmpty($this->params,true)){
			return false;
		}
		
		$this->config   = $config;
		$this->userData = $userData;
		
		return $callBack($this->params,$this->baseURL,$this->pageSum,$this);

	}

	
	/**
	 * 保存处理脚本返回的数据到指定路径,多用于回调插件中的调用
	 * @param $url
	 */
	function saveData($url){
			
		$savePath = STATIC_PATH.$this->type.'/'.$this->name;
		if(!is_dir($savePath)){
			mkdir($savePath);
		}
		
		$data     = filer::doHttpReq(website::$url['host'].$url['real_url']);
		$filePath = realpath($savePath).'/'.$url['save_path'];
		
		return filer::writeFile($filePath,$data);
		
	}
	
	/**
	 * 获取一个参数的值
	 * @param string $pname 参数名称
	 * @param mixed $param 参数内容
	 * @return string 
	 */
	function getVal($pname,$param){
		return str_replace($pname.'=','',$param);
	}
	
	
	
	/**
	 * 获取一个参数项，组合值,可以根据参数值的不同类型
	 * 返回对应的用组
	 * @param $name 参数名称
	 * @param $values 参数值
	 * @retrun array
	 */
	protected function getParamVal($name,$values){
		
		if(!validate::isNotEmpty($name) || $values==null){
			return false;
		}
		
		/**判定类型获取数据**/
		if(is_resource($values)){
			$var =  $this->bulidParamVal( $this->dbCon->fetchLink($values) );
		}else if(is_array($values)){
			$var =  $this->bulidParamVal($name, $values );
		}
		
		return $var;
		
	}
	
	
	/**
	 * 获取一个参数项，组合值,供getParamVal调用
	 * 返回对应的用组
	 * @param $name 参数名称
	 * @param $values 参数值
	 * @retrun array
	 */
	protected function bulidParamVal($pName,$values){
		
		if(!validate::isNotEmpty($pName) || $values==null){
			return false;
		}
		
		if(is_array($values)){
			
			$GLOBALS['pName'] = $pName;
			
			if(!function_exists('staticArrMap')){
				function staticArrMap($item){
					global $pName;
					return $pName.'='.$item;
				}
			}
			
			$data = array_map('staticArrMap',$values);
		}else{
			$data = array( $pName.'='.$values );
		}
		
		return $data;
		
	}
	
	
}


?>