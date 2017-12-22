<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 本类主要是获取网站运行时的系统的调用类
 * 它提供网站全局的相关内容
 * @author: Hiron Jack
 * @since 2013-7-3
 * @version: 1.0.0
 * @example:
 * 配置说明
 * 		'base_classes'=>array(
			'自动加载的基类'
		),
		
		'base_func'=>array(
			'自动加载的方法库
		),
		
		'base_config'=>array(
			'自动加载的配置',
		),
		
		'base_apps'=>array(
			'自动加载的应用控制器'
		),
		
		手动注册一个类
		'register_class|model|query'=>array(
			'oldClass'=>'newClass',
		),
		
 * */
 
class System{
	
	/**系统基路径*/
	public $sysBaseDir = '';
	
	/**系统配置数据.system文件*/
	protected $config = null;
	
	/**系统名称**/
	public $name='';
	
	/**自动加载的系统配置数*/
	public $baseConfig = array();
	
	
	/**自动加载相关类对应的目录*/
	protected $sysExtMap = array(
		'app'=>'apps',
		'model'=>'models',
		'query'=>'query',
		'service'=>'service',
		'class'=>'classes',
	    'func'=>'functions',
		'config'=>'config',
	);
	
	/**保存加载的配置文件数据*/
	protected static $loadedCfgData = array();
	
	
	/**
	 * 构造函数,加载配置并加载类库，启动默认应用
	 * @param string $system系统路径名
	 */
	public function __construct($system){
		
		
		$time = website::curRunTime();
		$this->name = $system;
		$this->sysBaseDir = realpath(MODULE_DIR.'/'.$system.'/').'/';
		$confPath =  $this->sysBaseDir.$this->name.'.system.php';
		
		if(!is_file($confPath)){
			$this->error($system.'模块系统不存在或不合法，请检查配置项',2);
		}	
		
		
		/**加载系统的入口配置**/
		$this->config = include($confPath);
		$this->loadBases();
	}
	
	
	/**
	 * 获取app的对象
	 * @param string $appName app的名字
	 * @return app
	 */
	public function getApp($appName){
		$this->loadApp($appName);		
		$class = $appName.'App';
		$app =  new $class();
		
		return $app;
	}
	
	
	
	/**
	 * 加载app
	 */
	public function loadApp($appName){
		
		$appName  = StrObj::getClassName($appName);
		$path     = $this->getClassPath($appName,'app');
		$appName  = StrObj::addStrR($appName,'App');
		
		$isBase = false;
		$time = website::curRunTime();
		if($path=='' || !is_file($path)){
			return false;
		}
		
	
		if(!class_exists($appName) && !$isBase){
			include($path);
		}
		
		if(!class_exists($appName) ){
			return false;
		}

		$appName::$_system[strtolower($appName)] = $this;
		
		website::debugAdd('加载应用'.$appName,$time);
		return true;
	}


	/**
	 * 获取service对象
	 * @param string $m 服务名称
	 * @return service
	 */
	public function service($m=null){
		
		$m = StrObj::def($m,$this->name);
		$class = StrObj::getClassName($m).'Service';
		if(!class_exists($class)){
			$this->loadService($m);
		}
		
		$cls =  new annotation($class);
		
		return $cls;
	}
	
	
	
	/**
	 * 加载服务
	 * @param string $m 用务名称
	 */
	public function loadService($s){
		
		$s = StrObj::getClassName($s);
		$servicePath = $this->getClassPath($s,'service');
		
		if($servicePath=='' || !is_file($servicePath)){
			return false;
		}
		
		include($servicePath);
		$class = $s.'Service';
		if(property_exists($class, '_system')){
			$class::$_system[strtolower($class)] = $this;
		}
		
		
	}
		
	
	
	/**
	 * 获取模块对象
	 * @param string $m 模块名称
	 * @param string $v 模块的关键值
	 * @return ORM
	 */
	public function model($m,$v=null){
		
		$class = StrObj::getClassName($m).'Model';
		if(!class_exists($class)){
			$this->loadModel($m);
		}
		
		if(!class_exists($class)){
			return ORM::factory($m,$v);
		}
		
		return new $class($v);
	}
	

	/**
	 * 加载模型
	 * @param string $m 模型名称
	 */
	public function loadModel($m){
		
		$m         = StrObj::getClassName($m);
		$modelPath = $this->getClassPath($m,'model');

		if($modelPath=='' || !is_file($modelPath)){
			return false;
		}
		
		include($modelPath);
		
		$class = $m.'Model';
		if(property_exists($class, '_system')){
			$class::$_system[strtolower($class)] = $this;
		}
		
		return true;
	}
	
	/**
	 * 获取查询对象
	 * @param  string $m 查询名称
	 */
	public function query($m=null){
		
		$m = StrObj::def($m,$this->name);
		$class = StrObj::getClassName($m).'Query';
		if(!class_exists($class)){
			$this->loadQuery($m);
		}
		
		return new annotation($class);
	}
	

	
	public function loadQuery($m){
		
		$t = website::curRunTime();
		$m = StrObj::def($m,$this->name);
		$m         = StrObj::getClassName($m);
		$modelPath = $this->getClassPath($m,'query');

		if($modelPath=='' || !is_file($modelPath)){
			return false;
		}
        
		include($modelPath);
		$class = $m.'Query';
		if(property_exists($class, '_system')){
		    $class::$_system[strtolower($class)] = $this;
		}
		
		website::debugAdd('加载模块查询'.$m,$t);
		return true;
	}	
	/**
	 * 加载默认应用、类、函数库
	 */
	public function loadBases(){
		
		if($this->config == null){
			return false;
		}
		
		$t = website::curRunTime();
		if(validate::isNotEmpty(arrayObj::getItem($this->config,'base_config')) == true){
			
			foreach($this->config['base_config'] as $k =>$config){
				
				$dir = $this->sysBaseDir.'base/config/'.$config.'.config.php';
				if(is_file($dir)){
					$this->baseConfig[$config] = include($dir);
				}else{
					$this->error('找不到配置'.$dir.'文件！',1);
				}
			}
			
		}
		
		if(validate::isNotEmpty(arrayObj::getItem($this->config,'base_classes')) == true){
			
			foreach($this->config['base_classes'] as $k =>$class){
				
				/**多线程中，可能会重复加载*/
				if(class_exists($class)){
					continue;
				}
				
				$dir = $this->sysBaseDir.'base/classes/'.$class.'.class.php';
				if(is_file($dir)){
					require($dir);
					if(property_exists($class,'_system')){
						$class::$_system[$class] = $this;
					}
				}else{
					$this->error('找不到类文件'.$dir.'文件！',1);
				}
			}
			
		}
		
		
		if(validate::isNotEmpty(arrayObj::getItem($this->config,'base_func')) == true){
			
			foreach($this->config['base_func'] as $k =>$func){
				
				if(class_exists($func)){
					continue;
				}
				
				$dir = $this->sysBaseDir.'base/functions/'.$func.'.func.php';
				if(is_file($dir)){
					require($dir);
				}else{
					$this->error('找不到函数库'.$dir.'文件！',1);
				}
			}
		}
		
		
		if(validate::isNotEmpty(arrayObj::getItem($this->config,'base_apps'))  ==true ){
			
			foreach($this->config['base_apps'] as $k =>$app){
				
				$dir = $this->sysBaseDir.'base/apps/'.$app.'.app.php';
				if(is_file($dir)){
					require($dir);
				}else{
					$this->error('找不到应用'.$dir.'文件！',1);
				}
				
			}
			
		}
		
		website::debugAdd('加载模块'.$this->name.'基础资源',$t);
		
	}
	
	/**
	 * 加载相关系统类库
	 * @param string $clsName
	 */
	public function loadClass($clsName){
		
		if(class_exists($clsName)){
			return false;
		}	

		$t = website::curRunTime();
		$clsName  = StrObj::getClassName($clsName);
		$clsPath  = $this->getClassPath($clsName,'class');

		if($clsPath=='' || !is_file($clsPath)){
			return false;
		}
		
		@include($clsPath);
		
		property_exists($clsName,'_system') && $clsName::$_system[strtolower($clsName)] = $this;
		website::debugAdd('加载模块类'.$clsName,$t);
		
		return true;
		
	}
	
	/**
	 * 加载系统相关函数库
	 * @param string $func
	 */
	public function loadFunc($func){
		
		$t = website::curRunTime();
		$clsName  = StrObj::getClassName($func);
		$clsPath  = $this->getClassPath($clsName,'func');

		if($clsPath=='' || !is_file($clsPath)){
			return false;
		}
		
		@include($clsPath);
		$clsName::$_system[strtolower($clsName)] = $this;
		website::debugAdd('加载模块助手函数'.$clsName,$t);
		
		return true;
	}
	
	
	/**
	 * 加载相关系统配置,查找*.config的文件
	 * @param string $cnf 配置名称
	 * @return array()
	 */
	public function loadConfig($cnf){
		
		/**保存到静态变量，减少重负查找*/
		$saveKey = $this->name.'_'.$cnf;
		if(isset(self::$loadedCfgData[$saveKey])){
			return self::$loadedCfgData[$saveKey];
		}
		
		
		$t = website::curRunTime();
		$cnfPath = str_replace('.','/',$cnf);
		
		$envPath = $this->sysBaseDir.'config/'.$cnfPath.'_'.website::$env.'.config.php';
		$path = $this->sysBaseDir.'config/'.$cnfPath.'.config.php';
		
		if(!is_file($path)){
			$path = $envPath;
		}
		
		if(!is_file($path)){
			website::debugWarning($cnf.'配置不存在');
			return array();
		}
		
		
		
		$res = include($path);
		self::$loadedCfgData[$saveKey] = $res;
		website::debugAdd('加载模块配置'.$cnf,$t);
		return $res;
	
	}
	
	
	protected function getClassPath($name,$type){
	    
	    $name  = $this->getRegistgerName($name, $type);
		$files = StrObj::getClassPath($name);
		$modelPath = '';
		if($files!=null){
			
			$dir = $this->sysExtMap[$type];
			foreach($files as $k=>$f){
				$f = strtolower($f);
				$modelPath = $this->sysBaseDir."/$dir/$f.$type.php";
				if(is_file($modelPath)){
					break;
				}
			}
		}
		
		return $modelPath;
				
	}
	
	
	/**
	 * 获取相关类注册的名称
	 * @param string $name 类名
	 * @param string $type 类型
	 * @return string
	 */
	protected function getRegistgerName($name,$type){
	    
	    /**
	     * 手动指定代理类来切换对应的类
	     */
	    if(arrayObj::getItem($this->config,'register_'.$type)!=null){
	        if(!isset($this->config['register_'.$type][$name])){
	            $name = $this->config['register_'.$type][$name];
	        }
	    }

	    
	    return $name;
	}
	
	
	/**
	 * 写入配置信息到配配文件
	 * @param string $cnf 配置名称
	 * @param mixed $data 配置数据
	 */
	public function writeConfig($cnf,$data){
		
		$path = $this->sysBaseDir.'config/'.str_replace('.','/',$cnf).'.config.php';
		return filer::saveVarsFile($data,$path);
		
	}
	
	
	
	/**
	 * 显示错误
	 * @param string  $msg 错误消息
	 * @param int $level  错误级别
	 */
	private function error($e,$level){
		$e.= ';Class:'.$this->name;	
		website::error($e,$level,3);
	}
	

	
	
	
}


?>