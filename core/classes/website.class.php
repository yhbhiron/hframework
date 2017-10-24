<?php
if(!defined('IN_WEB')){
	exit;
}
/**
 * 本类主要是获取网站运行时的一些系统信息和系统方法,
 * 它提供网站全局的相关内容
 * @author: Hiron Jack
 * @since 2015-7-3
 * @version: 1.0.3
 * @example:
 * website::init();
 * website::loadClass('dataBase');
 * website::log('xxx','error');
 */

class website{
	
	/**
	 * 站点地址信息
	 * **/
	public static $url = array();
	
	
	/**系统配置，格式为：配置名称=>值**/
	public static $config    = array();
	
	protected static $loadedConfigData = array();
	
	/**系统调试信息**/
	private static $debugArr = null;
	
	/**系统开始运行的时间**/
	private static $startTime;
	
	/**站点的状态,值有stop-停止、closed-关闭、init-初使化、running-正在运行**/
	private static $webState = 'stop';
	
	/**
	 * 错误对象 
	 * @var error 
	*/
	private static $error = null;
	
	
	/**系统的模板引擎**/
	public static $ui;
	
	/**环境,local,dev,online**/
	public static $env = 'local';
	
	
	public static $break;
	
	
	/**事件已加载**/
	protected static $loadedEvent = array();
	
	
	/**args,cli参数**/
	protected static $args;
	
	/**
	 * 站点响应格式
	 * */
	public static $responseType = 'html';
	
	
	/**
	 * 已加载系统
	 * */
	protected static $loadedSys = array();	
	
	
	
	/**
	 * 已加载模块
	 * **/
	protected static $modules = array();
	
	
	/**
	 * 自动加载的文件
	 */
	protected static $autoFiles;
	
	
	
	/**
	 * 路由处理器
	 * @var rewriteRule
	 */
	public static $route = null;
	
	/**
	 * 当前执行的应用
	 * @var app
	 */
	protected static $curApp;
	
	
	Const ENV_CLI = 'cli';
	Const ENV_TEST = 'test';
	Const ENV_PROD = 'online';
	Const ENV_DEV  = 'local';
	Const ENV_TEST_FORM	= 'test_form';
	
	/**
	 * 初使化系统，其中固定加载了,error、validate类库
	 * 基本配置项
	 */
	public static function init($args=null){
		
		self::$startTime = self::curRunTime(true);
		
		/**需要把参数放在getAddr之前*/
		self::$args = $args;
		
		/**设置环境**/
		if(php_sapi_name() == 'cli'){
			self::$env =self::ENV_CLI;
		}else{
			
			if(!isset($_SERVER['env'])){
				if($_SERVER['HTTP_HOST']=='localhost' || $_SERVER['HTTP_HOST'] =='127.0.0.1'){
					self::$env = self::ENV_DEV;
				}else{
					self::$env = self::ENV_PROD;
				}
			}else{
				self::$env  = $_SERVER['env'];
			}
			
			ob_start();
		}	
		
		if( in_array(self::$env,array(self::ENV_CLI,self::ENV_TEST_FORM)) ){
			self::$break = "\n";
		}else{
			self::$break = "<br />";
		}

		require WEB_FUNC_DIR.'callback.func.php';
		require WEB_FUNC_DIR.'filer.func.php';
		require WEB_FUNC_DIR.'strobj.func.php';
		require WEB_FUNC_DIR.'arrayobj.func.php';		
		
		
		self::findAutoLoadFiles();
		self::loadConfig('web');
		date_default_timezone_set(self::$config['timezone']);
		self::$url = self::getAddr();
		self::initSess();
		httpd::setMimeType(self::$responseType);
		
		if(isset(self::$config['memory'])){
			@ini_set('memory_limit',self::$config['memory']);
		}
		
		
		self::$webState = 'init';
		self::$error = new ErrorHandler();
		self::$config['log_file'] = '';
		website::doEvent('page_start');
		
		/**错误函数和相关调试信息,回收放在错误之前注册，以免回收失效**/
		set_error_handler(array('callback','errorHandle'));
		register_shutdown_function(array('app','recycle'));
		register_shutdown_function(array('callback','errorLastHandle'));
		register_shutdown_function(array('website','debugWrite'));
		register_shutdown_function(array('website','doEvent'),'page_end');		
		
		if(self::$env !=self::ENV_CLI && self::$env!=self::ENV_TEST_FORM){
		    
			$t = self::curRunTime(true);
			self::$route = new rewriteRule();
			self::$route->routeToURL();
			website::debugAdd('执行路由',$t);
			
		}
		

		
	}
	
	
	/**
	 * 启动系统
	 */
	public static function run($app=null,$act=null,$direct=true){
		
		if($app==null){
			$def  = arrayObj::getItem(website::$config,'def_app');
			$app = request::get('app');
			$app = $app!='' ?  $app : $def;
		}

		website::loadAppClass($app,true);
		$className = StrObj::getClassName($app).'App';
		if(!class_exists($className,false)){
			httpd::status404();
			return false;
		}
		
		$class = new annotation($className);
		self::$curApp = $class; 
		$resp = $class->run($act);
		
		self::$webState = 'running';
		
		return $resp;
	}
	
	
	
	/**
	*取得站点的相关地址信息
	*host 访问根目录
	*abs_page绝对地址无参数
	*abs_url 绝对地址有参数
	*rel_page 相对地址无参数
	*rel_url 相对地址有参数
	*page短地址无参数
	*url短地址有参数
	*from 来路地址
	*safe_from 安全来路，是否为自己的来路
	*@return array 地址信息数组
	**/
	protected static function getAddr(){
		
		if(self::$env == 'cli'){
			
			$_SERVER['REDIRECT_URL'] = NULL;
			$_SERVER['HTTP_HOST'] = 'localhost';
			$_SERVER['QUERY_STRING'] = '';
			$_SERVER['HTTP_REFERER'] = NULL;
			$_SERVER['REQUEST_URI']  = '';
			
			/**把参数变成get参数,格式a=b#c=d**/
			$param = self::$args;
			if($param!=''){
				
				$reqParam = explode('#', $param);
				foreach($reqParam as $k=>$p){
					
					 if(strstr($p,'=')){
					 	$temp = explode('=',$p);
					 	request::get($temp[0],arrayObj::getItem($temp,1));
					 }else{
					 	request::get($p,'');
					 }
					 
				}
			}			
		}
		
		$addr  = array();
		$inFolder = realpath(APP_PATH)!=realpath($_SERVER['DOCUMENT_ROOT']);
		if($inFolder){
			$curPath = substr($_SERVER['SCRIPT_NAME'],strlen(basename(APP_PATH))+2);
			$uri     = substr($_SERVER['REQUEST_URI'],strlen(substr(realpath(APP_PATH),strlen($_SERVER['DOCUMENT_ROOT'])))+1);
			$redURL  =  substr(arrayObj::getItem($_SERVER,'REDIRECT_URL'),strlen(basename(WEB_ROOT))+2);
		}else{
			$curPath = substr($_SERVER['SCRIPT_NAME'],1);
			$uri     = substr($_SERVER['REQUEST_URI'],1);
			$redURL  =  substr(arrayObj::getItem($_SERVER,'REDIRECT_URL'),1);
		}
	
		/**系统根访问目录**/
		$addr['host']=  $inFolder ?  (arrayObj::getItem($_SERVER,'HTTPS') == 'on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].'/'.filer::relativeHostPath(APP_PATH).'/' : 'http://'.$_SERVER['HTTP_HOST'].'/' ;
		$temp = explode('?',$uri);
		
		/**地址信息**/
		if(arrayObj::getItem($_SERVER,'REDIRECT_URL')!=''){
			
			$addr['abs_page']  = $addr['host'].$redURL;
			$addr['abs_url']   = $addr['host'].$uri;
			$addr['rel_page']  = $redURL;
			$addr['rel_url']   = $uri;
			$addr['page']      = $redURL;	
			$addr['url']      = $uri;	

		}else if(arrayObj::getItem(self::$config,'rewrite_type') == 2 ){
			
			$addr['abs_page']  = $addr['host'].$temp[0];
			$addr['abs_url']   = $addr['host'].$uri;
			$addr['rel_page']  = $temp[0];
			$addr['rel_url']   = $uri;
			$addr['page']      = $temp[0];	
			$addr['url']      = $uri;	
							
		}else{
			
			$addr['abs_page']  = $addr['host'].$curPath;
			$addr['abs_url']   = !empty($_SERVER['QUERY_STRING']) ? $addr['abs_page'].'?'.$_SERVER['QUERY_STRING']: $addr['abs_page'];
			$addr['rel_page']  = $curPath;
			$addr['rel_url']   = !empty($_SERVER['QUERY_STRING']) ? $addr['rel_page'].'?'.$_SERVER['QUERY_STRING']: $addr['rel_page'];
			$addr['page']      = basename($_SERVER['SCRIPT_FILENAME']);
			$addr['url']       = !empty($_SERVER['QUERY_STRING']) ? $addr['page'].'?'.$_SERVER['QUERY_STRING']: $addr['page'];
		}
		
		/**来路地址**/
		$addr['from']      = arrayObj::getItem($_SERVER,'HTTP_REFERER');
		$addr['safe_from'] = filer::isMineURL($addr['from']) ? StrObj::striptags($addr['from']) : ''; 
		$addr['ip']    = request::getUserIP();
		$addr['domain']= $_SERVER['HTTP_HOST'];
		 
		
		$addr['uri']    = $temp[0];
		if(isset($temp[1]) && $temp[1]!=''){
			
			$gets = explode('&',$temp[1]);
			foreach($gets as $k=>$g){
				if($g!=''){
					$get =  explode('=',$g);
					if($get[0]!=''){
						request::get($get[0],urldecode(arrayObj::getItem($get,1)));
					}
				}
			}			
		}
		

		return $addr;
	}	
	
	

	
	
	/**
	 * 显示站点错误，由错误类提供
	 * @param string $err 错误信息
	 * @param int $level 错误级别
	 * @param int $type 错误类型
	 * @param int $code 错误代码
	 */	
	public static function error($err,$level,$type=3,$code=0){
		
		if(self::$error){
			
			self::$error->showError($err,$level,$type,$code);
			
		}else{
			
			throw new Exception($err);
			
		}
		
	}
	
	
	private static function findAutoLoadFiles(){
		
		if(isset(self::$autoFiles)){
			return self::$autoFiles;
		}
		
		$time     = self::curRunTime(self::$webState=='stop');

		$dir = APP_PATH.'apps/';
		$GLOBALS['app_files'] = array();
		filer::scandir($dir,function($f){
			
			if(!is_file($f)){
				return;
			}
			
			$f = filer::realpath($f);
			$GLOBALS['app_files'][md5($f)] = array('loaded'=>false,'file'=>$f);
		});
		
		$GLOBALS['class_files'] = array();
		$dir = APP_PATH.'base/classes/';
		filer::scandir($dir,function($f){
			
			if(!is_file($f)){
				return;
			}
			
			$f = filer::realpath($f);
			$GLOBALS['class_files'][md5($f)] = array('loaded'=>false,'file'=>$f);
		});	
		
		$dir = MODULE_DIR.'/base/classes';;
		filer::scandir($dir,function($f){
			
			if(!is_file($f)){
				return;
			}
			
			$f = filer::realpath($f);
			$GLOBALS['class_files'][md5($f)] = array('loaded'=>false,'file'=>$f);
		});	

		$dir = MODULE_DIR.'/base/functions';;
		filer::scandir($dir,function($f){
			
			if(!is_file($f)){
				return;
			}
			
			$f = filer::realpath($f);
			$GLOBALS['class_files'][md5($f)] = array('loaded'=>false,'file'=>$f);
		});	
				

		$dir = WEB_CLASS_DIR;
		filer::scandir($dir,function($f){
			
			if(!is_file($f)){
				return;
			}
			
			$f = filer::realpath($f);
			$GLOBALS['class_files'][md5($f)] = array('loaded'=>false,'file'=>$f);
		});	

		$dir = WEB_FUNC_DIR;
		filer::scandir($dir,function($f){
			
			if(!is_file($f)){
				return;
			}
			
			$f = filer::realpath($f);
			$GLOBALS['class_files'][md5($f)] = array('loaded'=>false,'file'=>$f);
		});	
		
		
		self::$autoFiles = array(
			'class_files'=>$GLOBALS['class_files'],
			'app_files'=>$GLOBALS['app_files'],
		);
		
		self::debugAdd('加载自动加载文件',$time,true);
		return self::$autoFiles;
				
	}
	
	/**
	 * 从自动加载的文件中加载一个类
	 * @param string $file 文件名
	 * @param string $type 类型
	 */
	private static function loadAutoFile($file,$type){
		if(self::$autoFiles == null){
			return false;
		}
		
		if(!isset(self::$autoFiles[$type])){
			return false;
		}
		
		$list = self::$autoFiles[$type];
		$file = filer::realpath($file,false);
		if(!$file){
			return false;
		}
		
		$key = md5($file);
		if(!isset($list[$key])){
			return false;
		}
		
		$file = $list[$key];
		if($file['loaded'] == true){
			return true;
		}
		
		self::$autoFiles[$type][$key]['loaded'] = true;
		include $file['file'];
		return true;
	}
	
	
	/**
	 * 加载类库 a.b.c或a_b_c
	 * @param string $clsName 类名
	 * @return boolean true/false 是否成功加载 
	 */
	public static function loadClass($clsName){

		$path   = str_replace(array('.','_'),'/',$clsName);
		$virtrual = preg_match('/\.|_/',$clsName);
				
		if(!$virtrual){
			if(class_exists($clsName)){
				return true;
			}			
		}
		
		if($virtrual){
			$classDir = APP_BASE_PATH.'/classes/'.strtolower($path).'.class.php';
			if(self::loadAutoFile($classDir,'class_files')){
				return true;
			}
		}else{
			
			$classDir = APP_BASE_PATH.'/classes/'.strtolower($clsName).'.class.php';
			if(!self::loadAutoFile($classDir,'class_files')){
				
				$files = StrObj::getClassPath($clsName);
				foreach($files as $k=>$f){
					$classDir = APP_BASE_PATH.'/classes/'.$f.'.class.php';
					if(self::loadAutoFile($classDir,'class_files')){
						return true;
					}
				}
				
			}else{
				return true;
			}
		}
		
		
		/**是否存在类库文件**/
		if($virtrual ){
			$classDir = WEB_CLASS_DIR.'/'.strtolower($path).'.class.php';
			if(self::loadAutoFile($classDir,'class_files')){
				return true;
			}
		}else{
			
			$classDir = WEB_CLASS_DIR.'/'.strtolower($clsName).'.class.php';
			if(!self::loadAutoFile($classDir,'class_files')){
			
				$files = StrObj::getClassPath($clsName);
				foreach($files as $k=>$f){
					$classDir = WEB_CLASS_DIR.'/'.$f.'.class.php';
					if(self::loadAutoFile($classDir,'class_files')){
						return true;
					}
				}
			}else{
				return true;
			}
		}

		
		return false;
		
	}
	
	
	/**
	 * 获取应用中的app类
	 */
	public static function loadAppClass($app,$onlyApp=false){
		
		
		$t = self::curRunTime();
		$path = StrObj::getClassName($app);
		if($onlyApp == false && strtolower(substr($path,'-3')) == 'app'){
			$path = substr($path,0,-3);
		}
		
		$classDir = APP_PATH.'/apps/'.strtolower($path).'.app.php';
		if(!self::loadAutoFile($classDir,'app_files')){
			$files = StrObj::getClassPath($path);
			foreach($files as $k=>$f){
				
				$classDir = APP_PATH.'/apps/'.$f.'.app.php';
				if(self::loadAutoFile($classDir,'app_files')){
					return true;
				}
				
			}
		}else{
			return true;
		}	
		

		return false;
	}
	
	/**
	 * 加载函数
	 * @param string $funcName
	 * @return true/false 是否加载成功
	 */
	public static function loadFunc($funcName){
		
		$path   = str_replace(array('.','_'),'/',$funcName);
		$virtrual = preg_match('/\.|_/',$funcName);
		
		if(!$virtrual){
			if(class_exists($funcName)){
				return true;
			}			
		}
		
		if($virtrual){
			$classDir = APP_BASE_PATH.'/functions/'.strtolower($path).'.func.php';
			if(self::loadAutoFile($classDir,'class_files')){
				return true;
			}
		}else{
			
			$classDir = APP_BASE_PATH.'/functions/'.strtolower($funcName).'.func.php';
			if(!self::loadAutoFile($classDir,'class_files')){
				
				$files = StrObj::getClassPath($funcName);
				foreach($files as $k=>$f){
					$classDir = APP_BASE_PATH.'/functions/'.$f.'.func.php';
					if(self::loadAutoFile($classDir,'class_files')){
						return true;
					}
				}
				
			}else{
				return true;
			}
		}
		
		
		/**是否存在类库文件**/
		if($virtrual ){
			$classDir = WEB_FUNC_DIR.'/'.strtolower($path).'.func.php';
			if(self::loadAutoFile($classDir,'class_files')){
				return true;
			}
		}else{
			
			$classDir = WEB_FUNC_DIR.'/'.strtolower($funcName).'.func.php';
			if(!self::loadAutoFile($classDir,'class_files')){
			
				$files = StrObj::getClassPath($funcName);
				foreach($files as $k=>$f){
					$classDir = WEB_FUNC_DIR.'/'.$f.'.func.php';
					if(self::loadAutoFile($classDir,'class_files')){
						return true;
					}
				}
			}else{
				return true;
			}
		}
		
		return false;	
			
	}
	
	
	/**
	 * 加载配置文件,可以根据环境加载配置
	 * @param string $confName 配置文件名
	 * @param boolean $addtoConfig 是否加入到系统配置中
	 * @return boolean true成功时，当addtoConfig为false时返回指定配置变量
	 */
	public static function loadConfig($confName,$addtoConfig=true){
		
		if(isset(self::$loadedConfigData[$confName])){
			return self::$loadedConfigData[$confName];
		}
		
		$confName = strtolower($confName);
		$path    = str_replace('.','/',$confName);
		$envPath = $path.'_'.website::$env;
		$confName   = strstr($path,'/') ? trim(strrchr($path,'/'),'/') : $confName;
		
		$confDir = WEB_CONFIG_DIR.'/'.$path.'.config.php';
		$envDir = WEB_CONFIG_DIR.'/'.$envPath.'.config.php';
		$time = self::curRunTime(self::$webState == 'stop');
		$conf = array();
		
		if(is_file($confDir)){
			$conf = arrayObj::extend(arrayObj::forceToArray(@include($confDir),true), $conf);
		}

		/**是否存在配置文件**/
		if(is_file($envDir)){
			$conf = arrayObj::extend(arrayObj::forceToArray(@include($envDir),true), $conf);
		}
		
		
		/**模块级配置*/
		$moduleConf = self::loadModuleBases($confName,'config');
		$conf = arrayObj::extend(!is_array($moduleConf) ? array() : $moduleConf,$conf);			
		
		/**合并应用级的配置信息**/
		$confDir = APP_BASE_PATH.'/config/'.$path.'.config.php';
		$envDir = APP_BASE_PATH.'/config/'.$envPath.'.config.php';
		
		if(is_file($confDir)){
			$conf = arrayObj::extend(arrayObj::forceToArray(@include($confDir),true), $conf);
		}
		
		if(is_file($envDir)){
			$conf = arrayObj::extend(arrayObj::forceToArray(@include($envDir),true), $conf);
		}
		
		self::debugAdd('加载配置'.$confName,$time,self::$webState=='stop');
		/**是否需要加入到系统配置中，如果需要则加入到config中，不需要则返回该配置**/
		if($addtoConfig){	
			if(!isset(self::$config[$confName]) || self::$config[$confName]==null){
				
				if($conf == null ){
					return false;
				}
				
				self::$config = array_merge(self::$config,$conf);
			}
			
		}
		
		self::$loadedConfigData[$confName] = $conf;
		return $conf;
				
	}
	
	
	/**
	 * 加入调试信息到调试数组
	 * @param string $str
	 * @param number $time 计算时间的止始时间
	 * @param boolean $force 是否强制添加
	 * @allowHTML是否支持html输出
	 */
	public static function debugAdd($str,$time=0,$force=false,$allowHTML=false,$color='blue'){
		
		/**开启调试模式时，才加入到调试信息中**/
		if( (isset(self::$config['debug']) && self::$config['debug'] == true) || $force){
			
			$tstr = '';
			if($time>0){
				$tstr = '<font color="green">('.((self::curRunTime($force)-$time)*1000).' ms)</font>';	
			}
			
			
			$break = self::$break;
			if($allowHTML == true){
				self::$debugArr.= '<font color="'.$color.'">'.$str.'</font>'.$tstr.$break;
			}else{
				self::$debugArr.= '<font color="'.$color.'">'.htmlspecialchars($str).'</font>'.$tstr.$break;
			}
			
		}
	}
	
	/**
	 * 追加错误信息调式
	 */
	public  static function debugError($str,$time=0,$force=false,$allowHTML=false){
		self::debugAdd($str,$time,$force,$allowHTML,'red');
	}
	
	/**
	 * 追加警告信息调式
	 */
	public  static function debugWarning($str,$time=0,$force=false,$allowHTML=false){
		self::debugAdd($str,$time,$force,$allowHTML,'#ff6500');
	}	
	
	/**
	 * 输出调试信息到浏览器,如果开启了调试或开启了写入到调试文件的功能
	 */
	public static function debugWrite(){
		
		$log = self::$config!=null && isset(self::$config['debug_log_path']) && is_dir(self::$config['debug_log_path']);
		if(!$log && !self::$config['debug']){
			return;
		}
		
		$break = self::$break;
		
		/**系统结束时间**/
		$sysRunEndTime = self::curRunTime();
		
		$output = array();
		$output[] = self::$debugArr;
		$output[] =  'SQL执行时间:'.arrayObj::getItem($GLOBALS,'sql_time',0).' ms';
		$output[] = '页面执行时间:'.(($sysRunEndTime - self::$startTime)*1000).' ms';
		$output[] = 'PHP版本:'.PHP_VERSION.'；系统根目录:'.WEB_ROOT.'; 操作系统:'.PHP_OS.'; '.'访问目录:'.self::$url['host'];
		$output[] = '当前文件：'.self::$url['page'];
		$output[] = '可用内存：'.ini_get('memory_limit').'; 内存使用：'.(memory_get_usage()/1024/1024).'M';
		$output[] = 'POST:'.htmlspecialchars(var_export(request::post(),true));
		$output[] = 'GET:'.htmlspecialchars(var_export(request::get(),true));
		$output[] = 'SESSION:'.htmlspecialchars(var_export($_SESSION,true));
		$output[] = 'FILES:'.htmlspecialchars(var_export(request::files(),true));
		$output[] = 'COOKIE:'.htmlspecialchars(var_export(request::cookie(),true));
		$output[] = '魔术引号:'.htmlspecialchars(var_export(get_magic_quotes_runtime(),true));
		$output[] = '地址信息:'.htmlspecialchars(var_export(self::$url,true));
		$output[] = 'SERVIER:'.nl2br(htmlspecialchars(var_export($_SERVER,true)));
		
		$inc = get_included_files();
		$incFiles = '引用文件:'.count($inc).$break;
		if($inc!=null){
			$incFiles.=implode($break,$inc);
		}
		$output[] = $incFiles;
		
		$noHTMLText = StrObj::stripTags( str_replace('<br />',"\n",implode("\n",$output)) );
		
		/**日志记录*/
		if($log){
			
			$file = '';
			if(self::$curApp && self::$curApp->getCurAction()!='' ){
				$file = strtolower(
					str_replace(array('/','\\','~','?','.'),'_',self::$curApp->getAppName().'_'.self::$curApp->getCurAction())
				);
			}else if(self::ENV_CLI == self::$env){
				$file = 'cli';
			}
			
			if($file != ''){
			
				file_put_contents(
					self::$config['debug_log_path'].'/debug_'.$file.'.log',
					$noHTMLText."\n页在返回内容：".ob_get_contents().
					"\n页面错误：".var_export(self::$error->getErrors(),true)
				);
			}
		}			
		
		/**调试显示*/
		if(arrayObj::getItem(self::$config,'debug') == true){
			
			if(self::$env == 'cli' || self::$env == self::ENV_TEST_FORM){
				
				echo '调试信息'.$break;
				echo $noHTMLText;
				
			}else if(self::$responseType == 'json'){
				
				$obConent = ob_get_contents();
				$a = array();
				if($obConent!=''){
					$a = json_decode($obConent,true);
					$a = !is_array($a) ? array('datas'=>$obConent!='' ? $obConent : $a) : $a;
				}
				
				ob_clean();
				$a['debug'] = $noHTMLText;
				echo json_encode($a);
				
			}else{
				
				if(isset(self::$config['debug_tpl_file']) && is_file(self::$config['debug_tpl_file'])){
					include self::$config['debug_tpl_file'];
				}
				
			}
		}
		
		self::clearVar($output);
		
	}
	
	
	/**
	 * 获取当前运行的时间，精确到毫秒,用于调试时的效率计算
	 * @param boolean $force 是否强制执行
	 */
	public static function  curRunTime($force=false){
		
		if( (!isset(self::$config['debug']) || self::$config['debug'] == false) && !$force){
			return;
		}
		
		list($msec,$sec)=explode(' ',microtime());
		return ((float)$msec+(float)$sec);
		
	}	
	
	/**
	 * 自动加类库
	 * @param string $clsName 类名
	 */
	public static  function autoload($clsName){
		
		$t = self::curRunTime();
		$res  = self::loadClass($clsName);
		$res == false  && $res = self::loadFunc($clsName);
		$res == false  && $res = self::loadModuleBases($clsName,'class');
		$res == false  && $res = self::loadModuleBases($clsName,'func');
		$res == false && $res = self::loadModuleClasses($clsName);
		$res == false && $res = self::loadAppClass($clsName);
		
		self::debugAdd('自动加载类:'.$clsName,$t);
	}
	
	/**
	 * 获取模块相关类、方法或配置
	 * @param string $name
	 * @param string $type config,func,config
	 */
	public static function loadModuleBases($name,$type='config'){
		
		
		$dir = MODULE_DIR.'/base/';
		if(!is_dir($dir)){
			return false;
		}
		
		if($type == 'class'){
			
			$dir.='classes/'.$name.'.class.php';
			return self::loadAutoFile($dir,'class_files');
	
		}else if($type == 'func'){	
			
			$dir.='functions/'.$name.'.func.php';
			return self::loadAutoFile($dir,'class_files');
			
		}else if($type == 'config'){
			
			$envDir = $dir.'config/'.$name.'_'.self::$env.'.config.php';
			$cnfData = array();
			
			$dir.='config/'.$name.'.config.php';
			if(is_file($dir)){
				$cnfData = arrayObj::forceToArray(@include($dir),true);
			}

			if(is_file($envDir)){
				$cnfData = arrayObj::extend( arrayObj::forceToArray(@include($envDir)),$cnfData);
			}
			
			return $cnfData;
		}
		
		
	}
	
	/**
	 * 加载模块中的类库
	 * @param string $name 类名
	 */
	public static function loadModuleClasses($name){
		
		if(class_exists($name)){
			return true;
		}
		
		$t = self::curRunTime();
		if(self::$modules == null){
			
			$path = MODULE_DIR;
			$dir  = scandir($path);
			if($dir == null){
				return false;
			}
			
			$modules = array();
			foreach($dir as $k=>$m){
				
				if($m == '.' || $m=='..'){
					continue;
				}
				
				$m = strtolower($m);
				$mpath = $path.'/'.$m.'/'.$m.'.system.php';
				
				if(is_file($mpath)){
					$modules[] =  strtolower($m);
				}
				
			}
			
			self::$modules = $modules;
		}
		
		if(self::$modules == null){
			return false;
		}
		
		foreach(self::$modules as $k=>$m){

			if( strtolower(substr($name,-3)) == 'app'){
				$finded = website::system($m)->loadApp(substr($name,0,-3));
			}else if( strtolower(substr($name,-5)) == 'query'){
				$finded = website::system($m)->loadQuery(substr($name,0,-5));
			}else if( strtolower(substr($name,-5)) == 'model'){
				$finded = website::system($m)->loadModel(substr($name,0,-5));
			}else if( strtolower(substr($name,-7)) == 'service'){
				$finded = website::system($m)->loadService(substr($name,0,-7));
			}else{
				$finded = website::system($m)->loadClass($name);
			}
			
			if($finded){
			    break;
			}
		}
		
		website::debugAdd('查找模块类'.$name,$t);
		return false;
		
	}
	
	
	/**
	 * 引用系统
	 * @param string $system 系统名称
	 * @param boolean $direct 是否为直接访问，默认为false
	 * @param string $app 引用的系统的应用的名称,默认不指定
	 * @return system 如果没有指定应用则返回系统对象，否则返回应用对象
	 */
	public static function system($system){
		
		if(!validate::isNotEmpty($system)){
			return false;
		}
		
		if( !is_dir(MODULE_DIR.str_replace('.','/',$system)) ){
			self::error('引用的系统'.$system.'不存在！',2,3);
			return false;
		}
		
		if(isset(self::$loadedSys[$system])){
			return self::$loadedSys[$system];
		}
		
		
		$sysObj = new system($system);
		self::$loadedSys[$system] = $sysObj;
		return $sysObj;
	}
	
	
	
	
	/**
	 *在现在项目外， 创建一个独立的项目，包括核心
	 * @param string $webName 网站名称
	 */
	public static function buildWebsite($webName){
		
		$docRoot = self::$env == self::ENV_CLI ?  dirname(WEB_ROOT): $_SERVER['DOCUMENT_ROOT'];
		$dir = $docRoot.'/'.$webName;
		
		if(!is_dir($dir) ){
			mkdir($dir);
		}else{
			return false;
		}
		
		$core     = WEB_ROOT.'core';
		$coreNew  = $dir.'/core';
		$newApp = $dir.'/appdemo';
		$system   = $dir.'/systems';
	
		
		$temp     =  WEB_ROOT.'temp';
		$newTemp  = $dir.'/'.basename($temp);
		
		$plug     =  self::$config['plugin_dir'];
		$newPlug  = $dir.'/'.basename($plug);
		
		$data     =  WEB_ROOT.'data';
		$newData  = $dir.'/'.basename($data);
		
		
		!is_dir($coreNew) && mkdir($coreNew);
		filer::copyDir($core,$coreNew);
			
		self::createApp($newApp);
		
		filer::mkdir($newPlug.'/ui');
		filer::mkdir($newPlug.'/events/cli');
		filer::mkdir($newPlug.'/events/page_start');
		filer::mkdir($newPlug.'/events/page_end');
		
		filer::copyDir($plug.'/ui',$newPlug.'/ui');
		filer::copyDir($plug.'/events/cli',$newPlug.'/events/cli');
		
		!is_dir($newTemp) && mkdir($newTemp);
		filer::mkdir($newTemp.'/logs/errors');
		filer::mkdir($newTemp.'/logs/debug');
		filer::mkdir($newTemp.'/logs/mysql');
		filer::mkdir($newTemp.'/compiled');
		filer::mkdir($newTemp.'/cache');
		filer::mkdir($newTemp.'/session');
		
		!is_dir($newData) && mkdir($newData);
		!is_dir($system) && mkdir($system);
		
		return true;
	}
	
	
	/**
	 * 在现有基础代码内，创建一个应用，共用核心
	 * @param string $newApp
	 */
	public static function createApp($newApp){
	    
	    if(!is_dir($newApp)){
	        $newApp = WEB_ROOT.basename($newApp);
	    }
	    
		filer::mkdir($newApp.'/apps');
		filer::mkdir($newApp.'/base/config');
		filer::mkdir($newApp.'/base/classes');
		filer::mkdir($newApp.'/views');
		filer::copy(WEB_ROOT.'/index.php.sample',$newApp.'/index.php');
	    
	}
	
	
	/**
	 * 重新更新应用的内核
	 * @param string $webName 站点名称
	 */
	public static function rebuildCore($webName){
		
		$docRoot = self::$env == self::ENV_CLI ?  dirname(WEB_ROOT): $_SERVER['DOCUMENT_ROOT'];
		$dir = $docRoot.'/'.$webName;
		if(validate::isNotEmpty($dir) == false){
			return false;
		}
		
		
		$ui    = WEB_ROOT.'plugins/ui';
		$newUi =  $dir.'/plugins/ui';
		
		filer::copyDir(CORE_DIR,$dir.'/core');
		filer::copyDir($ui,$newUi);
		
		
		
	}
	
	

	
	/**
	 * 记录系统日志
	 * @param string $str 日志内容
	 * @param string $type 日志类别，由用户订义
	 */
	public static function log($str,$type){
		
		if(!self::$config['logs']){
			return;
		}
		
		if(!is_file(self::$config['log_file'])){
			
			self::debugAdd('日志文件不存在!');
			return false;
			
		}
		
		$logStr = array();
		$time = date('Y-m-d H:i:s');
		$logStr['type'] = $type;
		$logStr['time'] = $time;
		$logStr['url']  = self::$url['abs_url'];
		$logStr['msg']  = $str;	
		$logStr['cookie'] = request::cookie();
		$logStr['session'] = $_SESSION;
		$logStr['request'] = request::req();
		$logStr['server'] = $_SERVER;
			
		if(self::$config['logs_type'] == 1){
			
			$logStr['msg'] = $logStr['msg'];
			$old = filer::$apiMode;
			filer::$apiMode = false;
			$str = json_encode($logStr,defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0);
			filer::writeFile(self::$config['log_file'],$str."\r\n",FILE_APPEND | LOCK_EX);
			filer::$apiMode = $old;
			
		}else{
			query::factory()->insert('logs',$logStr)->execute();
		}
		
	}
	
	/**
	 * 从文件中读取日志信息到数组中
	 * @param string $logFile 日志文件
	 * @return array 日志数据
	 */
	public static function getLog($logFile){
		
		if(!file_exists($logFile)){
			return false;
		}
		
		$data = file($logFile);	
		if(validate::isNotEmpty($data,true)){
			
			foreach($data as $k=>$str){
				
				if(trim($str) != ''){
					$data[$k] = json_decode(trim($str),true);
				}
				
			}
				
		}
		
		return $data;
	}
		
	

	
	/**
	 * 清除变量
	 * @param mixed $var 变量
	 */
	public static function clearVar(&$var){
		$var = null;
		unset($var);
	}
	

	
	/**
	 * 启动session
	 */
	public static function initSess(){
		
		self::loadConfig('session',true);
		
		/**兼容不同版本*/
		if(PHP_VERSION<7){
    		session_set_save_handler(
    			array('session',"open"), 
    			array('session',"close"), 
    			array('session',"read"), 
    			array('session',"write"), 
    			array('session',"destroy"), 
    			array('session',"gc")
    		);
		}else{
		    $session = new sessionHandler();
		    session_set_save_handler($session,true);
		}
		
		$sid= '';
		if(self::$env !== self::ENV_CLI){
			
			$key = 'hiron_requser_id';
			if(httpd::cookie($key) == ''){
			    httpd::setCookie($key, self::makeSessionId(),365*86400*5);
			}
			
			/**远程手动指定session_id*/
			$sessionCookieName = 'hiron_requser_sid';
			if(arrayObj::getItem($_POST,'session_id_sign') ==  md5(arrayObj::getItem($_POST,'USER_SESS_ID').website::$config['secret']) 
			        && arrayObj::getItem($_POST,'USER_SESS_ID')!=''){
				$sid = $_POST['USER_SESS_ID'];
			}else{
				
				$defSessId = httpd::cookie($sessionCookieName,true,'-');
				if( $defSessId == ''){
					
					$id = self::makeSessionId();
					$sid = $id.'-'.httpd::cookieSalt($id);
					
				}else{
					$sid = $defSessId;
				}
				
			}
				
			
			ini_set('session.name',$sessionCookieName);
			if($sid!=''){
			    session_id($sid);
			}
			
		}
		

		
		session_start();
		
	}
	
	
	protected static function makeSessionId(){
		$sid = md5(arrayObj::getItem($_SERVER,'HTTP_USER_AGENT','nobot').website::$url['host'].website::$url['ip'].StrObj::randStr(32,StrObj::RND_MIXED));
		return $sid;
	}
	
	
	
	/**
	 * 事件插件
	 * @param string $ev 事件名称
	 * @param array $params 事件所需的参数
	 */
	public static function doEvent($ev,$params=null){
		
		$event = new event($ev,$params);
		return $event->run();
	}
	
	/**
	 * 加载通用插件,函数处理形式
	 * @param string $plugnName 插件名称如product.sellprice
	 * @param string $alias 插件扩展别名,可选
	 * @return array();
	 */
	public static function loadPlugin($plugName,$alias=''){
		
		$plugPath = PLUG_DIR.str_replace('.','/',$plugName);
		if(!is_dir($plugPath)){
			return false;
		}
		
		$list = scandir($plugPath);
		$plugins = array();
		$plugReg = '/([a-z0-9_]+)\.plug\.php/';
		
		foreach($list as $k=>$fname){
			
			$fullPath = realpath($plugPath.'/'.$fname);
			if($fname!='.' && $fname!='..' && is_file($fname) && preg_match($plugReg,$fname,$m)){
				
				$plugins = array(
					'func'=>$m[1].$alias.'Plug',
					'path'=>$fullPath, 
				);
				
			}
			
			
		}
		
		return $plugins;
		
	}
	
	
}