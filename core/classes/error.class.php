<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 本类主要是提供网站的错误支持
 * 它提供网站全局的相关内容
 * @author Hiron Jack
 * @since 2013-7-24
 * @version 1.0.8
 * @example $error = new error();
 * $error->showError('用户错误',2,1);
 */

 class error{
	 
	  
	  /**错误显示模板**/
	  protected $errTpl ='';
	  
	  /**错误代码表**/
	  protected $codeData = array();
	  
	  /**错误级别**/
	  protected $errLevel = array();
	  
	  /**错误类别**/
	  protected $errType = array();
	  
	  /**自定义错误信息**/
	  public static $errorInfo = null;
	  
	  /**
	   * 所有错误信息列表
	   */
	  protected static $errors = array();
	  
	  public function __construct(){
		
	  	/**是否开启错误显示**/
	  	@ini_set('display_errors','Off');
	  	if(website::$config['show_errors']){
	  		error_reporting(website::$config['error_level']);
	  	}else{
	  		error_reporting(0);
	  	}
	  	
	  	/**加载错误模板**/
	  	$this->errTpl   = website::$config['error_tpl_dir'];
	  	$this->errLevel = array(
	  		1=>'警告',
	  		2=>'错误',
	  		3=>'注意',
	  		
	  	);
	  	
	  	$this->errType = website::loadConfig(website::$config['error_type_cnf'],false);
	  	
	  }
	  
	  public static function getErrors(){
	  	 return self::$errors;
	  }
	  
	  public static function getErrorString(){
	  	return implode(';',array_map(function($v){ return $v['message']; },self::$errors));
	  }
	  
	 /**
	  * 显示错误信息
	  * @param string $msg 错误消息
	  * @param string $level 错误级别
	  * @param string $type 错误类别
	  * @param int $code 错误代码
	  */
	  public function showError($msg,$level,$type,$code=0){

		if(!StrObj::isUTF8($msg)){
			$msg = @iconv('gbk',website::$config['charset'].'//ignore',$msg);
		}
	  	 
	    $errorInfo = array(
		   	'type_name'=>$this->errType[$type],
		   	'level_name'=>$this->errLevel[$level],
		 	'error'=>$code,
		 	'code'=>$code,
		 	'level'=>$level,
		 	'message'=>$msg,	  	 
	    );
	    
	    $traceInfo = $this->getTracer($msg);
		$errorInfo['trace'] = $traceInfo['array'];
		self::$errors[] = $errorInfo;
		$this->log($errorInfo['code'].';'.$errorInfo['type_name'].';'.$errorInfo['level_name'].';'.$errorInfo['message'].';'.$traceInfo['string'] );
		
		if(website::$env == website::ENV_PROD){
			$errorInfo['message'] = '系统错误';
		}
		 
		 httpd::status500();
		 if(website::$config['show_errors']){
		 	
		 	  if(website::$responseType == 'json'){
		 	  	
		 	  		httpd::setMimeType('json');
		 	  		httpd::status200(true);
		 	  		
		 	  		$errorStr = $this->getErrorString();
				 	$msg = array(
			 			'info'=>array(
			 				'error'=>1,
				 			'code'=>1,
				 			'message'=>$errorStr,
			 			),
			 			
			 			'msg'=>$errorStr,
			 			'code'=>500,
			 			'error'=>1,
				 	);
				 	
				 	
				 	$msgout = function()use($msg){
						$obConent = ob_get_contents();
						$a = array();
						$a = $msg;
						if($obConent!=''){
							$c = json_decode($obConent,true);
							if(is_array($c)){
								$a = array_merge($a,$c);
							}else{
								$a['datas'] = $obConent;
							}
						}
						
						echo json_encode($a);
						
					};

					if($level == 2){
						$msgout();
					}else{
						register_shutdown_function($msgout);
					}
				
				
		 	  }else if(file_exists($this->errTpl)){
				  include $this->errTpl;
			  }else{
			  	
			  	echo($errorInfo['level_name'].';'.$errorInfo['type_name'].';'.$msg);
			  	
			  }
				  
		  }		
		  
		  /**
		   * 如果忽略了，不强制退出
		   */
		  if($level == 2 && (error_reporting() & (E_ALL^E_WARNING^E_NOTICE^E_USER_ERROR^E_USER_NOTICE^E_DEPRECATED ))){
				exit;  
		  }
		  
	  }
	  


	
	/**
	 * 获取错误的追踪信息
	 */
	protected function getTracer($msg){
		
		if(self::$errorInfo !=null){
			
			$str = $this->getErrDetail(self::$errorInfo);
			self::$errorInfo = null;
			return $str;
				
		}
		
		try{
			throw new Exception($msg);
		}catch(Exception $e){
			
			$info = $e->getTrace();
			return $this->getErrDetail($info);
		}
		
	}
	
	/**
	 * 获取错误的详情信息
	 * @param array $info 错误trace数组
	 * @return string 
	 */
	protected function getErrDetail($info){
		
		$showKey = array(
			'file'=>'文件',
			'line'=>'错误行',
			'function'=>'函数',
			'class'=>'相关类',
			'args'=>'参数',
		);

		$errListArr = array();
		$errListStr = '';
		
		foreach($info as $k=>$err){
			
			
			if(arrayObj::getItem($err,'class')=='error' || arrayObj::getItem($err,'function') == 'error'){
				continue;
			}
				
			if(arrayObj::getItem($err,'class')=='callback' 
			&& in_array(arrayObj::getItem($err,'function'),array('errorLastCallback','errorCallback'))
			){
				continue;
			}
			
			$temp  = array();
			$codeBlock = array();
			
			foreach($err as $j=>$msg){
				
				if(arrayObj::getItem($showKey,$j)!=null){
					$temp[]=$showKey[$j].':'.(is_array($msg) ? @json_encode($msg,true) : $msg);
				}

			}
			
			$codeBlock['err_info'] = $temp;
			$codeBlock['err_detail'] = array();
			if(arrayObj::getItem($err,'file')!=null){
				$codeBlock['err_detail'] = $this->getScriptErrLine($err['file'],$err['line']);
			}
			
			$errListArr[] = $codeBlock;
			$errListStr.=implode(',',$temp);
			
			
		}

		return array('array'=>$errListArr,'string'=>$errListStr);
		
	}
	
	/**
	 * 获取错误行代码
	 * @param string $file 文件
	 * @param int $line 错误行
	 * @return array
	 */
	protected function getScriptErrLine($file,$line){
		
		if(!is_file($file)){
			return array();
		}
		
		$handle = fopen($file,'r');
		$k       = 1;
		$maxShow = 8;
		$start = $line - $maxShow >0 ? $line-$maxShow : 1;
		$end   = $line+$maxShow;
		
		$codeLine = array();
		while(!feof($handle)){
			
			$c = fgets($handle);
			if( $k>=$start && $k <=$end){
				
				$lineInfo = array('selected'=>false,'code'=>StrObj::toHtmlCode($c));
				if($k == $line){
					$lineInfo['selected'] = true;
				}
				
				$codeLine[$k] = $lineInfo;
				
			}else if($k>$line+$maxShow){
				break;
			}
			
			$k++;
			
			
		}
		
		fclose($handle);
		return $codeLine;
		
	}

	  /**
	   * 记录到日志
	   */
	  protected function log($msg){
	  	
	  	if(arrayObj::getItem(website::$config,'logs') ==true && arrayObj::getItem(website::$config,'logs_type') == 1 ){
			
			if(!is_writable(arrayObj::getItem(website::$config,'error_log_dir'))){
				exit('日志目录不存在或不可写!');
			}
			
			website::loadFunc('filer');
			website::$config['log_file'] =  filer::mkTimeFile('logs.txt',website::$config['error_log_dir']);
			
		}	

		website::log($msg,'error');
		  
	  }
	  
	 
	   
 }
 
 



?>