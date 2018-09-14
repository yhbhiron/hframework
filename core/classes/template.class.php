<?php
if(!defined('IN_WEB')){
	exit('Access Deny!');
}
/**
模板生成类
模板生成主要包括，对模板中模板标记的解板
流程控制解板主要有:if foreach 和 dbfor等流程块的解析
环境变量的解析:session,cookie和其它变量
系统插件的解析如textbox,msgbox等
支持cache缓存,插件和即时输出和gzip输出
@version 2.0.11
@author jackbrown;
@since 2013-07-28
@example
$t = new template();
$t->assign('title','测试标题');
$t->assign('header','标题sss一');
$t->assign('job','vip');

$list = array( array('name'=>'yhbhiron'),array('name'=>'john'),array('name'=>'jack'));
$t->assign('List',$list);
$t->assign('body','<h1> This is my body</h1>');
$t->assign('my',array('name'=>'yhb'));
$t->display('test.html');
**/
class Template{
	
	/**左分隔符**/
	protected $leftSpe	= '';
	
	/**右分隔符*/
	protected $rightSpe = '';
		
	
	/**模板路径*/
	protected $tplDir = '';
	
	/*编译文件路径*/
	protected $compDir = '';
	
	/**图片路径**/
	protected $imgDir = '' ;
	
	/**js路径**/
	protected $jsDir = '' ;
	
	/**css路径**/
	protected $cssDir = '' ;
	
	
	/**插件目录**/
	protected $pluginDir = '';
	
	/*变量数组*/
	private $vars = array();
	
	/**foreach临时数组**/
	private $_foreach = array();
	
	
	/**插件信息**/
	private $plugins = array();
	
	/**模板地址**/
	protected $tplURL = '';
	
	/**脚本文件访问路径**/
	protected $jsURL  = '';
	
	/**图片文件访问路径**/
	protected $imgURL = '';
	
	/**css访问路径**/
	protected $cssURL = '';
	
	/**公共js**/
	protected $jsComURL = '';
	
	/**公共js**/
	protected $jsComDir = '';	
	
	/**是否开启调试模式，调试模式下没有缓存**/
	public $debug = false;
	
	
	/**当前模板文件**/
	protected $curTplFile = '';
	
	/**当前编译文件**/
	protected $curCompFile = '';
	
	/**
	 * 去除html注释
	 * @var boolean
	 */
	public $stripComment = true;
	
	
	/**
	 * 
	 * @param boolean $selfTpl 是否为自己的模板，自己的模板的情况下，不使用配置的模板
	 */
	public function __construct($selfTpl=false){
		
		
		/**加载配置项**/
		$cnfData = website::loadConfig('template',false);
		$this->leftSpe  = $cnfData['left_spe'];
		$this->rightSpe = $cnfData['right_spe'];
		
		if($selfTpl == true){
			return;
		}
		
		$this->tplDir   = $cnfData['tpl_dir'];
		$this->compDir  = $cnfData['comp_dir'];
		$this->pluginDir= $cnfData['plugin_dir']; 
		
		if(arrayObj::getItem($cnfData,'virtual_url_prefix')==null){
			
			$this->jsURL  =  !validate::isURL($cnfData['js_dir']) ? filer::getVisitURL($cnfData['js_dir']) : $cnfData['js_dir'];
			$this->imgURL =  !validate::isURL($cnfData['img_dir']) ? filer::getVisitURL($cnfData['img_dir']) : $cnfData['img_dir'] ;
			$this->cssURL =  !validate::isURL($cnfData['css_dir']) ? filer::getVisitURL($cnfData['css_dir']) : $cnfData['css_dir'];
			$this->jsComURL = !validate::isURL($cnfData['js_comm_dir']) ? filer::getVisitURL($cnfData['js_comm_dir']) : $cnfData['js_comm_dir'];
			$this->theme   =   filer::getVisitURL(VIEW_DIR);
			$this->tplURL =   filer::getVisitURL($this->tplDir);
		}else{
			$this->jsURL  = $cnfData['virtual_url_prefix'].filer::relativePath($cnfData['js_dir'],VIEW_DIR).'/';
			$this->imgURL  = $cnfData['virtual_url_prefix'].filer::relativePath($cnfData['img_dir'],VIEW_DIR).'/';
			$this->cssURL  = $cnfData['virtual_url_prefix'].filer::relativePath($cnfData['css_dir'],VIEW_DIR).'/';
			$this->jsComURL  = $cnfData['virtual_url_prefix'].filer::relativePath($cnfData['js_comm_dir'],VIEW_DIR).'/';
			$this->theme   =   $cnfData['virtual_url_prefix'].filer::relativePath(defined('THEME_DIR') ? THEME_DIR :VIEW_DIR,VIEW_DIR).'/';
			$this->tplURL =   $cnfData['virtual_url_prefix'].filer::relativePath($this->tplDir,VIEW_DIR).'/';
			
		}
		
		
		$this->jsDir    = $cnfData['js_dir'];
		$this->imgDir   = $cnfData['img_dir'];
		$this->cssDir   = $cnfData['css_dir'];
		$this->jsComDir = $cnfData['js_comm_dir'];
		
		
		if(!is_dir($this->tplDir)){
			
			$this->error($this->tplDir.'模板目录不存在!',2);
		}
		
		if(!is_dir($this->compDir)){
			
			$this->error($this->compDir.'编译路径目录不存在!',2);
		}		
		

		
	}
	
	
	/**
	 * 输出模板内容到浏览器
	 * @param string $tpl 模板名称
	 * @param boolean $cache 是否需要缓存
	 * @param int $time 缓存时间s
	 * @param boolean $inc 是否为插入模板
	 * @return string 模板执型后html
	 */
	public function display($tpl,$inc=false){
		
		$stime = website::curRunTime();
		$this->curTplFile  = $this->tplDir.'/'.$tpl;
		$this->curCompFile = $this->compDir.'/'.urlencode($tpl).'.php';	

		if(!is_file($this->curTplFile)){
						
			$this->error('找不到模板文件'.$this->curTplFile,2);
			return false;
		}
		
				
		/*调试信息*/
		if($inc){
			website::debugAdd('引用模板：'.$tpl);
		}else{
			website::debugAdd('当前主模板：'.$tpl);
		}
		
		/**及时输出**/
		if(!$inc){
			ob_start(function($c){
				if(website::$responseType == 'json'){
					$c = json_encode($c);
				}
				return $c;
			});
		}
		
		/**如果模板没有发生变化**/
		if(!$this->isModified()){
			
			if(!$inc){
				@require($this->curCompFile);	
			}else{
				@include($this->curCompFile);	
			}
			
			website::debugAdd('模板未改变，不需要编译,直接引用编译后文件');
			
		}else{
			
			website::debugAdd('模板已改变，需要重新编译');
			$this->compile($this->curTplFile,$this->curCompFile);

		}
		
		$content = ob_get_contents();
		if($inc){
			flush();
		}else{
			ob_end_flush();
		}
		
		website::debugAdd('模板'.$tpl.'执行时间',$stime);
		return $content;
		
	}
	

	
	
	/**
	 * 添加模板变量
	*@param mixed $varName 变量名称或数组列表
	*@param mixed $val 变量值
	*/
	public function assign($varName,$val=''){
		
		$argsNum = func_num_args();
		if($argsNum == 1 && is_array($varName)){
			
			foreach($varName as $k=>$v){
				$this->vars[$k] = $v;
			}
			
		}else{
			$this->vars[$varName] = $val;
		}
		
		return $this;
	}
	
	
	
	/**
	*清除所有变量
	**/
	public function clearAllVars(){
	
		website::clearVar($this->vars);
	}
	
	
	public function __destruct(){
		
		$this->clearAllVars();	
	}
	
	/**
	 * 清除字符串类型的默认又引号或引号
	 * @param string $var 变量值
	 * @return string
	 */
	public function trimQuote($var){
		
		$var = trim($var,"'");
		$var = trim($var,'"');
		
		return $var;
	}
	
	/**
	 * 获取代码编译后的执行结果html代码
	 * @param string $code
	 * @return string
	 */
	public function  fetchCode($code){
		
		if(!validate::isNotEmpty($code)){
			return false;
		}
		
		ob_start();
		$code = $this->toTargetCode($code);
		$tmpFile = WEB_ROOT.'temp/'.uniqid().'.php';
		filer::writeFile($tmpFile,$code);
		@require($tmpFile);
		@unlink($tmpFile);
		$c = ob_get_contents();
		ob_clean();
		return $c;

	}
	
	/**
	 * 获取代码编译后的文件执行结果html代码
	 * @param $file 文件路径 
	 * @return string
	 */
	public function fetchFile($file){
		
		$code = filer::readFile($file);
		if($code == ''){
			return false;	
		}
		
		return $this->fetchCode($code);
		
	}
	
	
	
	/**
	 * 编译模板文件
	 * @param string $tplFile 模板文件路径
	 * @param string $compFile 编译文件路径
	 * @param boolean $cache 是否缓存
	 * @param int $cacheTime 缓存时间 秒
	 */
	protected function compile($tplFile,$compFile){

		$code = file($tplFile);
		if($code == null){
			return false;	
		}
		
		$codeStr = @$this->toTargetCode($code);	
		$header = $this->addHeader($tplFile);		
		filer::writeFile($compFile,$header.$codeStr,LOCK_EX);
		return @include($compFile);
		
		
	}
	
	
	/**
	 * 压缩源码
	 * @param string $code 代码
	 * @return string $code;
	 */
	protected  function compress($code){

		$code = preg_replace('/\r+|\n+|\s+/',chr(32),$code);
		return $code;
		
	}
	
	
	
	/**
	 * 获取编译后的php代码
	 * @param string $code 模板代码内容
	 * @return string 返回编对后的代码，如果错误则返回空
	 */
	protected function toTargetCode($code){
		
		if(validate::isNotEmpty($code) == false ){
			return '';	
		}
		
		
		/*加载插件*/
		if($this->plugins==null){
			$this->loadPlugins();
		}
		
		$codeStr = '';
		if(is_array($code)){
			
			foreach($code as $n=>$line){
				
				$line = $this->fetchSource($line,$n);
				$codeStr.=$line;
								
			 }
			 
		}else{
			
			$codeStr = $this->fetchSource($code);
		}
		 
		$codeStr = $this->tidyCode($codeStr);			
		return $codeStr;
		
	}
	
	/**
	 * 编译源码
	 * @param string $line 源码行
	 * @param int $n 行数
	 * @return string 编译后的代码
	 */
	protected function fetchSource($line,$n=0){
		
		$line = $this->compilePlugins($line,true);
   		preg_match_all('/'.$this->leftSpe.'\s*(.+?)\s*'.$this->rightSpe.'/is',$line,$tagBlock);

   		if($tagBlock[1]!= null){

			if($this->plugins!=null){
				  
				 /**获取每行中有标记的代码，并编译，非标记代码不编译**/
				  foreach($tagBlock[0] as $k=>$c){
				  	
				  		$temp = $this->compilePlugins($c);
				  		$line = str_replace($c,$temp,$line);
				  		
				  }
				  
				  preg_match_all('/'.$this->leftSpe.'\s*(.+?)\s*'.$this->rightSpe.'/is',$line,$tagBlock);
				  
			  }
			  
			  /**获取每行中有标记的代码，并编译，非标记代码不编译**/
			  foreach($tagBlock[0] as $j=>$c){
			  	
			  	$old  = $c;
				$temp = $this->compileIfTag($c);
				$temp = $this->compileforeachTag($temp);
				$temp = $this->compileInclude($temp);
				$temp = $this->compiledbforTag($temp);
				$temp = $this->compileFunc($temp);
				$temp = $this->compileScript($temp);
				$temp = $this->compileCss($temp);
				$temp = $this->compileVars($temp);	
				
				/**如果改变了，刚把右左分隔符替换成php的分隔符**/
				if($old!=$temp){
					$temp = str_replace($this->leftSpe,'<?php ',$temp);
					$temp = str_replace($this->rightSpe,' ?>',$temp);	
				}
				
				$this->compileError($temp,$n+1);						
				$line = str_replace($c,$temp,$line);
				
			  }
		}	
		
		return $line;
		
	}
	
	/**
	*给编译文件添加头部信息
	*@param string $tpl模板文件
	*@param boolean $cache 是否可缓存此文件true/false;
	*@return string 设置的头部信息字符
	*/
	protected function addHeader($tpl){
	
		$time = filemtime($tpl);
		
		$header = "<?php
		/**
		@create-time:$time
		**/
		if(!defined('IN_WEB')){
			exit('Access Deny!');
		}
		
		?>\n";
				  
		return $header;
	}
	
	/**
	*读取编译文件的头部信息
	*@param string $tpl编译文件
	*@return array头部信息数组
	*/
	protected function readHeader($tpl){
		
		$fh = fopen($tpl,'r');
		$headers = array();
		
		while(!feof($fh)){
			
			$lineCode = trim(fgets($fh));
			if(substr($lineCode,0,1) == '@'){
				
				$hItem = explode(':',$lineCode);
				$headers[substr($hItem[0],1)] = $hItem[1];
			}
			
			if($lineCode == '?>'){
				
				break;
			}
		}
		
		fclose($fh);
		return $headers;
	}

	
	/**
	*加载插件函数
	*/
	protected function loadPlugins(){

		if(is_dir($this->pluginDir)){
			
			$sub = scandir($this->pluginDir);
			$temp = array();
			$time = website::curRunTime();
			$this->plugins['global'] = $this->plugins['normal'] = array();
			
			foreach($sub as $k=>$fname){
				
				if($fname!='.' && $fname!='..'){	
				
					$fullName = $this->pluginDir.'./'.$fname;
					$p = $fullName;
					
					if(is_file($p)){
						
						$f = basename($p);						
						if(preg_match('/^([a-z_]+)\.plug\.php$/i',$f,$m)){
							
							/**加载未加载的插件**/
							$funcName = $m[1].'PluginUI';
							if(arrayObj::getItem($this->plugins['global'],$funcName)==null &&  
							arrayObj::getItem($this->plugins['normal'],$funcName)==null ){
								
								@include($p);
								if(function_exists($funcName)){
									
									/**是否为全局插件**/
									if(isset($plugIsGlobal) && $plugIsGlobal === true){
										
										$this->plugins['global'][$funcName] = $funcName;
										
									}else{
										
										$this->plugins['normal'][$funcName] = $funcName;
									}
									$plugIsGlobal = false;
									website::debugAdd('加载模板插件'.$funcName);
									
								}
								
							}
							
						}//eof is preg_match
			
					}//eof is file
				}//eof is not .
			
			}//eof foreach
			
			website::debugAdd('加载模板插件',$time);
			
		}//eof is_dir
	
	}
	
	protected function isTag($code){
		
		$regExp = '/'.$this->leftSpe.'[a-z]+(.*?)'.$this->rightSpe.'/i';
		return preg_match($regExp,$code);
		
	}
	
	protected function isEnd($code){
		
		$regExp = '/'.$this->leftSpe.'\/[a-z]+\s*'.$this->rightSpe.'/i';
		return preg_match($regExp,$code);
		
	}
	
	
	/**
	 * 获取代码中的标签<{tagname param1='a' param2=>'b' }>
	 * @param string $tagName 标签名
	 * @param string $code 模板源代码
	 * @return array 
	 */
	 function getTagInfo($tagName,$code){
		
		$regExp = '/'.$this->leftSpe.$tagName.'(.*?)'.$this->rightSpe.'/i';
		$tagInfo = array();
		
		if(preg_match($regExp,$code,$tag)){
			
			$tagInfo['tpl_tag_name'] = $tagName;
			$paramStr = preg_split('/\s+/',$tag[1]);
			foreach($paramStr as $k=>$v){
				
				if(trim($v) != ''){
					
					$each = preg_split('/\s*=\s*/',trim($v));
					$tagInfo[trim($each[0])]  = $this->getVarVal(trim($each[1]));
					
				}

			}
			
		}
		
		return $tagInfo;
	}
	
	/**
	 * 获取变量的值
	 * @param string $var 变量字符
	 * @param boolean $quote 是否保留变量的 ' "字符
	 * @return string
	 */
	protected function getVarVal($var,$tag=true,$quote=true){
		if($var == ''){
			return "''";
		}
		
		
		if(preg_match('/^\'[^\']+\'|\"[^\"]+\"/',$var) && $tag){
			if($quote){
				return preg_replace('/\'|\"/','"',$var);;
			}else{
				return preg_replace('/\'|\"/','',$var);
			}
		}else if(is_numeric($var) && $tag){
			return $var;
		}else{
			
			$old = $var;
			/*foreach*/
			$var = preg_replace('/\$hiron\.foreach\.([a-zA-Z0-9_\-]+)\.iteration/i','$this->_foreach[\'$1\'][\'iteration\']',$var);
			$var = preg_replace('/\$hiron\.foreach\.([a-zA-Z0-9_\-]+)\.last/i','($this->_foreach[\'$1\'][\'last\'] == $this->_foreach[\'$1\'][\'iteration\'])',$var);
			/*session*/
			$var = preg_replace('/\$hiron\.session\.([a-zA-Z0-9_\-:%\$]+)/i','session::get(\'$1\')',$var);
			
			$var = preg_replace('/\$hiron\.get\.([a-zA-Z0-9_\-:%\$]+)/i','request::get(\'$1\')',$var);
			
			/**超级变量**/
			$var = preg_replace('/\$hiron\.post\.([a-zA-Z0-9_\-:%\$]+)/i','request::post(\'$1\')',$var);
			$var = preg_replace('/\$hiron\.cookie\.([a-zA-Z0-9_\-:%\$]+)/i','arrayObj::getItem($_COOKIE,\'$1\')',$var);
			$var = preg_replace('/\$hiron\.server\.([a-zA-Z0-9_\-]+)/i','$_SERVER[\'$1\']',$var);
			$var = preg_replace('/\$hiron\.global\.([a-zA-Z0-9_\-]+)/i','$GLOBALS[\'$1\']',$var);
			$var = preg_replace('/\$hiron\.class\.([a-zA-Z0-9_\-]+)\.(\$?[a-zA-Z0-9_]+)/i','$this->vars[\'$1\']->$2',$var);
			$var = preg_replace('/\$hiron\.object\.([a-zA-Z0-9_]+)\.(\$?[a-zA-Z0-9_]+)/i','$1::$2',$var);
			$var = preg_replace('/\$hiron\.request\.([a-zA-Z0-9_\-]+)/i','request::req(\'$1\')',$var);
			$var = preg_replace('/\$hiron\.theme/i','$this->theme',$var);
			$var = preg_replace('/\$hiron\.tpl_url/i','$this->tplURL',$var);
			$var = preg_replace('/\$hiron\.js_url/i','$this->jsURL',$var);
			$var = preg_replace('/\$hiron\.img_url/i','$this->imgURL',$var);
			$var = preg_replace('/\$hiron\.css_url/i','$this->cssURL',$var);
			
			/*数组变量*/
			$var = preg_replace('/\$([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+)/i','$this->vars[\'$1\'][\'$2\'][\'$3\'][\'$4\']',$var);
			$var = preg_replace('/\$([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+)/i','$this->vars[\'$1\'][\'$2\'][\'$3\']',$var);
			$var = preg_replace('/\$([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+)/i','$this->vars[\'$1\'][\'$2\']',$var);
			
			/*普通变量*/
			$var = preg_replace('/(?<!\'|::|\")\$(?!this|_SESSION|_REQUEST|_COOKIE|_GET|_POST|_SERVER)([a-zA-Z0-9_\-]+)/i','$this->vars[\'$1\']',$var);
			
			if($old == $var &&  $tag && substr($var,0,7)!='$this->'){
				if($quote && !preg_match('/^[a-z0-9_]+\(/i',$var)){
					return StrObj::addStrLR($var,'"');
				}else{
					return $var;
				}
			}
			
			return $var;
			
		}
		
		return $var;
	}

	/**
	 * 检没是否有错误
	 * @param string $code 模板代码
	 * 
	 */
	protected  function compileError($code,$line){
		
		if(preg_match('/'.$this->leftSpe.'|'.$this->rightSpe.'/',$code,$error)){
			$this->error($this->curTplFile.';无法实别的代码:'.htmlspecialchars($code).';line:'.$line,2);
		}
		
	}
	
	
	
	/**
	*编辑变量
	**/
	protected function compileVars($code){
		
		if(!preg_match_all('/'.$this->leftSpe.'\s*(.+?)\s*'.$this->rightSpe.'/is',$code,$tagBlock)){
			return $code;
		}
		
		$old = $code;
		/*给变量加echo*/
		$code = $this->getVarVal($code,false);
		$code = preg_replace('/'.$this->leftSpe.'\s*(\$([^=]+))\s*'.$this->rightSpe.'/is',
														'<?php echo $1; ?>',
														$code
				);
		
		$code = preg_replace('/'.$this->leftSpe.'\s*([a-z0-9_]+::\$?[a-z0-9_]+.*)\s*'.$this->rightSpe.'/is',
														'<?php echo $1; ?>',
														$code
				);
		
		if($old == $code && !$this->isTag($code) && !$this->isEnd($code)){
			$code = preg_replace('/'.$this->leftSpe.'\s*(.+?)\s*'.$this->rightSpe.'/is',
															'<?php  $1; ?>',
															$code
					);
					
		}
						
		return $code;

	}
	
	/**
	 * 整理代码
	 * @param string $code
	 * @return string 返加整理后的代码
	 */
	protected function tidyCode($code){
		
		$code = preg_replace('/\?>\s*<\?php/i',"\r\n",$code);
		if($this->stripComment == true){
		    $code = preg_replace('/<!--[^>]+-->/i','',$code);
		}
		
		$code = preg_replace_callback('/<script[^>]*>(.+?)<\/script>/is',function($m){
		    $lines  = preg_split("/\n/",$m[0]);
		    $str = "";
		    foreach($lines as $s){
		        $s = preg_replace('/^\s*\/\/.*/',"",$s);
		        $s = preg_replace('/;?\/\/.*$/',";",$s);
		        if($s!=''){
		            $str.=$s."\n";
		        }
		    }
		    return $str;
		},$code);
		
		if(Website::$env == Website::ENV_PROD){
			$code =$this->compress($code);
		}
		
		return $code;
	}
	
	/**
	*编译插件
	**/
	protected function compilePlugins($code,$global=false){
		
		if($this->plugins == null){
			return $code;
		}
		
		$temp = '';
		$plugins = $global == true ? $this->plugins['global'] : $this->plugins['normal'];
		if($plugins == null){
			return $code;
		}
		
		foreach($plugins as $func){
			
			$temp = $func($code,$this);	
			if($temp!=''){
				$code = $temp;	
			}
		}
		
		if($temp == ''){
			return $code;
		}
		return $temp;
	}
	
	
	
	/**
	*编译if标签
	*@param $code 模板源码
	**/
	protected function compileIfTag($code){
		
		
		$old = $code;
		$code = preg_replace('/'.$this->leftSpe.'(?<!\/|else)\s*if(.+)\s*'.$this->rightSpe.'/i','<?php if($1){ ?>',$code);
		$code = preg_replace('/'.$this->leftSpe.'\s*(else)\s*'.$this->rightSpe.'/','<?php }$1{ ?>',$code);
		$code = preg_replace('/'.$this->leftSpe.'\s*elseif\s*(.+)\s*'.$this->rightSpe.'/','<?php }else if($1){ ?>',$code);
		$code = preg_replace('/'.$this->leftSpe.'\s*\/if\s*'.$this->rightSpe.'/i','<?php } ?>',$code);
		if($old != $code){
			$code = $this->getVarVal($code,false);
		}
		return $code;
		
	}
	
	
	/**
	*编译function
	*@param $code 模板源码
	*/
	protected function compileFunc($code){
		$code = preg_replace('/'.$this->leftSpe.'\s*([a-z0-9A-Z_:->]+\(.*\))\s*'.$this->rightSpe.'/is',
				$this->leftSpe.' echo $1;'.$this->rightSpe,$code);
		return $code;
	}
	
	
	/**
	*编译foreach标签
	*针对标签 <{foreach item=xx form=$xx }>
	*@param $code 模板源码
	*/
	protected function compileforeachTag($code){

		$tag = $this->getTagInfo('foreach',$code);
		
		
		if($tag!=null){	
			
			website::debugAdd('编译foreach标签');
			if(ArrayObj::getItem($tag,'from') == null){
				$this->error('foreach的变量需为一数组!',2);	
				return $code;
			}
					
			if(ArrayObj::getItem($tag,'item') == null){
				$this->error('foreach的item不能为空!',2);	
				return $code;
			}		
			
			
			$vName   = $tag['item'];
			$kName   = arrayObj::getItem($tag,'key') == '' ? "'k'" : $tag['key'];
			$forName = arrayObj::getItem($tag,'name') =='' ? $tag['item'] : $tag['name'];
			
			$forStr = 
			"<?php 
			
				\$array = ".$tag['from'].";
				\$this->_foreach[$forName]['iteration'] = 0;
				if(validate::isNotEmpty(\$array)){
					
					flush();
					\$this->_foreach[$forName]['last'] = count(\$array);
					foreach(\$array as \$this->vars[$kName]=>\$this->vars[$vName]){ 
					
						\$this->_foreach[$forName]['iteration']++;
						flush();
					
					
			?>";
			
			$code = $forStr;
		}
		
		$code = preg_replace('/'.$this->leftSpe.'\s*\/foreach\s*'.$this->rightSpe.'/i',"<?php }}\n unset(\$array); ?>",$code);
		return $code;
		
	}
	

	
	/**
	*编译数据库读取
	*兼容foreach模式
	*针对标签 <{dbfor item=xx form=$xx name=xx key=xx }>
	*@param $code 模板源码
	*/
	protected function compiledbforTag($code){
			
		$tag = $this->getTagInfo('dbfor',$code);
		if($tag!=null){	
			
			website::debugAdd('编译dbfor标签');
			if($tag['from'] == null){
				$this->error('foreach的变量需为一数组!');	
				return $code;
			}
						
			$vName   = $tag['item'] =='' ? 'dbv' : $tag['item'];
			$kName   = $tag['key'] == '' ? 'dbk' : $tag['key'];
			$forName = $tag['name'] == '' ? $vName : $tag['name'];
			$cache   = $tag['cache'] == 'true' ? 'true' : 'false';
			
			$forStr = 
			"<?php
			
				 \$res = ".$tag['from']. ";
				  if(is_resource(\$res) || validate::isNotEmpty(\$res,true)){
					
				  	flush();
					\$this->_foreach[$forName]['iteration'] = 0;
					\$array = array();
					
					while(\$this->vars[$vName] = (is_array(\$res) ? current(\$res) : mysql_fetch_assoc(\$res))){
					
						\$this->_foreach[$forName]['iteration']++;
						array_push(\$array,\$this->vars[$vName]);
						is_array(\$res) && next(\$res);
						flush();

			 ?>";
				
			$code = $forStr;
			
		}
		
		$code = preg_replace('/'.$this->leftSpe.'\s*\/dbfor\s*'.$this->rightSpe.'/i',"<?php }}\n?>",$code);
		return $code;
		
	}
	
	
	
	
	/**
	*include file
	*<{include tpl='xx' cache=true/false time=number }>
	*/
	protected function compileInclude($code){
		
		$tag = $this->getTagInfo('include',$code);
		
		
		if($tag!=null){	

			website::debugAdd('编译include标签');
			$incArray = $tag;
			if(arrayObj::getItem($incArray,'tpl') == null){
				$this->error('include:tpl 不能为空!');	
			}
			
			$cache = arrayObj::getItem($incArray,'cache')!='true' ? 'false' : 'true';	
			$tpl = $incArray['tpl'];
			$incStr = "<?php  \$this->display(".$tpl.",true); ?>";
			$code = $incStr;
			
		}
		
		return $code;
	}
	
	
	
	/**
	*编译script
	*<{script src='xx,'xx' }>
	*/
	protected function compileScript($code){
		
		$tag = $this->getTagInfo('script',$code);
		
		
		if($tag!=null){	

			website::debugAdd('编译script标签');
			$scriptArray = $tag;			
			if($scriptArray['src'] == null){
				$this->error('引入的脚本文件不能为空!');
				return $code;
			}
			
			$src      = trim($scriptArray['src'],"'\"");	
			$compress = arrayObj::getItem($scriptArray,'compress') == 'true' ? 'true' : 'false';
			$merge    = trim( arrayObj::getItem($scriptArray,'merge_file'),"'\"");
			$files = '';	
					
			if(strstr($src,',')){
				
				$temp = explode(',',$src);
					
				foreach($temp as $k=>$file){
					
					$path    = $this->jsDir.$file;
					$comPath = $this->jsComDir.$file;
					
					if(is_file($path)){
						
						$file = '<?php echo $this->jsURL ?>'.$file;
						$path = '$this->jsDir.'.StrObj::addStrLR( filer::relativePath($path,$this->jsDir),"'");
						$files.="<script type=\"text/javascript\" src=\"$file?v=<?php echo filemtime($path) ?>\" ></script>";
						
					}else if(is_file($comPath)){
						
						$comPath = filer::relativePath($comPath);
						$file = '<?php echo $this->jsComURL ?>'.$file;
						$files.="<script type=\"text/javascript\" src=\"$file?v=<?php echo filemtime('$comPath') ?>\" ></script>";
						
					}
				}
				
				
			}else{
				
				$path = $this->jsDir.$src;
				$comPath = $this->jsComDir.$src;
				
				if(is_file($path)){
					
					$src = '<?php echo $this->jsURL ?>'.$src;
					$path = ' $this->jsDir.'.StrObj::addStrLR( filer::relativePath($path, $this->jsDir),"'");
					$files = "<script type=\"text/javascript\" src=\"$src?v=<?php echo filemtime($path) ?>\" ></script>";
					
				}else if(is_file($comPath)){
					
					$file = '<?php echo $this->jsComURL ?>'.$src;
					$files ="<script type=\"text/javascript\" src=\"$file?v=<?php echo filemtime('$comPath') ?>\" ></script>";
					
				}
					
			}
			
			if($files == ''){
				$this->error('script:引入的脚本文件'.$files.'不存在!',1);
				return $code;
			}
			
			$code = $files;
		}
		
		return $code;
	}
	
	/**
	 * 标签<{css href='xx,xx...' }>
	 */
	protected function compileCss($code){
		
		$tag = $this->getTagInfo('css',$code);
		
		
		if($tag!=null){	
			
			website::debugAdd('编译css标签');
			$cssArray = $tag;
			if($cssArray['href'] == null){
				$this->error('引入的css文件不能为空!',1);
				return $code;
			}
			
			
			$src      = trim($cssArray['href'],"'\"");	
			$compress = arrayObj::getItem($cssArray,'compress') == 'true' ? 'true' : 'false';
			$merge    = preg_replace('/\'|\"/','',arrayObj::getItem($cssArray,'merge_file'));
			$files = '';
			
			if(strstr($src,',')){
				
				$temp = explode(',',$src);
				foreach($temp as $k=>$file){
					
					$path = $this->cssDir.$file;
					if(is_file($path)){
						
						$file = '<?php echo $this->cssURL ?>'.$file;
						$path = '$this->cssDir.'.StrObj::addStrLR( filer::relativePath($path,$this->cssDir),"'");
						$files.="<link rel=\"stylesheet\" type=\"text/css\" href=\"$file?v=<?php echo filemtime($path) ?>\" />";
						
					}
				}
					
			}else{
				
				$path = $this->cssDir.$src;
				if(is_file($path)){
					
					$src = '<?php echo $this->cssURL ?>'.$src;
					$path = '$this->cssDir.'.StrObj::addStrLR( filer::relativePath($path,$this->cssDir),"'");
					$files = "<link rel=\"stylesheet\" type=\"text/css\" href=\"$src?v=<?php echo filemtime($path) ?>\" />";
					
				}
			}
			
			
			if($files == ''){
				$this->error('引入的css文件不存在!',1);
				return $code;
			}
			
			$code = $files;
		}
		
		return $code;
	}
	
	
	/**
	*模板文件是否已修改
	*@param string $tpl 模板名称
	*@return boolean true/false
	*/
	public function isModified($tpl=null){
		
		if($this->debug){
			return true;
		}
		
		if($tpl != null){
			
			$compPath = $this->compDir.'/'.urlencode($tpl).'.php';
			$tplPath  = $this->tplDir.'/'.$tpl;
			
		}else{
			
			$compPath = $this->curCompFile;
			$tplPath  = $this->curTplFile;
			
		}
		
		if(!file_exists($compPath)){
			
			return true;
		}
		
		$header = $this->readHeader($compPath);
		if($header['create-time']<filemtime($tplPath)){
		
			return true;
		}
		
		return false;
	}
	
	/**
	 * 显示文件操作消息
	 * @param string  $msg 错误消息
	 * @param int $level  错误级别
	 */
	 function error($e,$level){
	 	ob_end_clean();
		$e.= ';Class:'.get_class($this);	
		website::error($e,$level,3);
	}
	
}



?>