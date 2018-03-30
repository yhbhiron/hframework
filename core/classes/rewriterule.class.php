<?php
if(!defined('IN_WEB')){
	exit;
}

/**
 * 本类是定义相关url重定的功能
 * nginx配置，将所有请求指向index.php时才有效		
 * location ~ {    
 *  index index.html index.php;
 *  root D:/AppServ/www/cloud/cloud/;
    rewrite ^(.*)$  /index.php?$1 last;        		
  }
      
* @author: Hiron Jack
 * @since 2015-7-23
 * @version: 1.0.2
 * @example:
 */
class RewriteRule extends Model{
	
	
	/**重写配置数据**/
	protected $cnfData = array();
	
	public function __construct(){
		
		$this->cnfData = website::loadConfig('rewrite',false);
		uasort($this->cnfData,function($v1,$v2){
			$s1 = arrayObj::getItem($v1,'sort',0);
			$s2 = arrayObj::getItem($v2,'sort',0);
			
			if($s1 > $s2){
				return -1;
			}
			
			return 1;
		});
		
	}
	
	
	/**
	 * 添加一条重写规则
	 * @param string $page 直实脚本
	 * @param array $ruleConfig 配置数据:array()
	 * @param $param 固定参数
	 */
	public function addRule($name,$ruleConfig){
		
		if(arrayObj::getItem($ruleConfig,'from_uri')==null){
			website::debugAdd('url重写配置from_uri不能为空');
			return false;
		}
				
		
		$this->cnfData[$name] = $ruleConfig;
		
	}
	
	
	/**
	 * 获取指定key的地址,如果不存在key,则使用default规则，如果存在key,但key中的get_uri配置
	 * 不是回调，则直接返回带host+key的地址，其它，按get_uri的回调来返回
	 * 用于配置中的get_uri($p1,p2,$p3..)
	 * get_uri的参数不定，由实际需求确定,第一个参数为获取uri_to配置的回调get_uri(callback,key,array params())
	 * @param stirng $key 配置键值 
	 */
	public function getURL($key=null){
		
		$args   = func_get_args();
		if($key==null || !isset($this->cnfData[$key])){
			$key = 'default';
		}
		
		$cfg = ArrayObj::getItem($this->cnfData,$key);
		if($cfg == null || !is_callable($cfg['get_uri'])){
			return website::$url['host'].$key;
		}
		
		$route  = arrayObj::getItem($cfg,'url_to');
		
		/**快速获取url_to信息项的一个回调*/
		array_unshift($args,function($v)use($route){
			if($route == null){
				return $v;
			}
			
			return arrayObj::getItem($route,$v);
		});
			
		$callback = $cfg['get_uri'];
		return call_user_func_array($callback,$args);
			
	}
	
	

	/**
	 * 路由导向直实地址
	 */
	public function routeToURL(){
		
		if(arrayObj::getItem(website::$config,'rewrite_type') != 2){
			return false;
		}
		
		$page = website::$url['uri'];
		if($page == 'index.php'){
			return false;
		}
		
		$ext  = strtolower(substr(strrchr($page,'.'),1));
		$staticExt = array('jpg','png','gif','css','ttf','eot','bmp','txt','js','vbs','swf','woff','woff2');

		/**静态地址处理方式,不建议静态地址使用路由的方式*/
		if(in_array($ext,$staticExt) && is_file(APP_PATH.$page) ){
			
			website::$config['debug'] = false;
			$ftime = filemtime(APP_PATH.$page);
			$etag  = md5_file(APP_PATH.$page);
			
			header('Cache-Control: private');
			header('Last-Modified: '.gmdate('D, d M Y H:i:s',$ftime ) . ' GMT');
			header('Expires: '.time::timeFormat(time()+500,'D, d M Y H:i:s').' GMT');
			header('etag: '.$etag);
			header('Cache-Control:private,max-age=999999');
			header('X-Object-Get:Hiron Jack');
			httpd::setMimeType($ext);
			
			
			if( strtotime(arrayObj::getItem($_SERVER,'HTTP_IF_MODIFIED_SINCE'))== $ftime 
				|| arrayObj::getItem($_SERVER,'HTTP_IF_NONE_MATCH') == $etag ){
				httpd::status304();
				exit;
			}		
					
			echo file_get_contents(APP_PATH.$page);
			exit;
			
			
		}
		
		$uri = website::$url['uri'];
		if($uri == ''){
			return false;
		}
		
		if($this->cnfData == null){
			httpd::status404();
			return false;
		}
		
		/**
		 * 从配置中获取路由的设置
		 */
		foreach($this->cnfData as $name=>$rule){
			
			$uriRule   = arrayObj::getItem($rule,'from_uri');
			
			/**路由是一个回调函数*/
			if(is_callable($uriRule)){
				
				$result =  call_user_func($uriRule,$uri,$this);
				if($result){
					return;
				}
				
			}else{
			    
			    if(!validate::isNotEmpty($uriRule)) {
			        continue;
			    }
				
				$target =  arrayObj::getItem($rule,'url_to') ;
				$status  = arrayObj::getItem($target,'status');
				
				/**路由是一个正则规则或多个规则**/
				if(!is_array($uriRule)){
				    
				    $uriRule = '/'.$uriRule.'/i';
				    preg_match($uriRule,$uri,$m);
				    
				}else{
				    
				    foreach($uriRule as $k=>$u){
				       
				       $u = '/'.$u.'/i';
				       if(@preg_match($u,$uri,$m)){
				           break;
				       }
				       
				    }
				}
				
				
				if($m!=null){
					
					$app    = arrayObj::getItem($m,1);
					$act    = StrObj::getClassName( arrayObj::getItem($m,2) );
					
					if(is_callable($target)){
					    return call_user_func($target,$m);
					}else if(validate::isNotEmpty($target,true)){
					    /**有设置url_to则按设置指定app和act即可*/
                        if(arrayObj::getItem($target,'url')!=''){
							
							if($status == 301){
								httpd::status301($target['url']);
								exit;
							}else{
								header('Location: '.$target['url'],true,302);
								exit;
							}
							
						}else{
							
							$statusCall = 'status'.$status;
							if(is_callable(array('httpd',$statusCall)) && $status!=301 && $status!=302){
								call_user_func(array('httpd',$statusCall));
							}	

							request::get('app', $this->compileParam(arrayObj::getItem($target,'app'),$m) );
							request::get('act',$this->compileParam(arrayObj::getItem($target,'act'),$m) );
							
							$params = arrayObj::getItem($target,'params');
							if($params!=null){
								foreach($params as $pname=>$pvalue){
									request::get($pname,$this->compileParam($pvalue,$m));
								}
							}
							
							return;	
						}
						
						
					}else{			
								
						if($app != null){
							
								
							$statusCall = 'status'.$status;
							if(is_callable(array('httpd',$statusCall)) && $status!=301 && $status!=302){
								call_user_func(array('httpd',$statusCall));
							}	
													
							request::get('app',$app);
							request::get('act',$act);
							return;
								
						}
					}
					
					
				}
				//end 是否匹配到了uri
			}
		}
		/**end foreach cnf data*/
		
		
		httpd::status404();
		exit;
		
	}
	
	
	/**
	 * 把数组装成字符地址参数a=b&c=d
	 * @param array $params array(id=>'111','333'=>'xx')
	 * @return string
	 */
	public function buildUrlParams($url,$params=array()){
		if($params == null){
			return $url;
		}
		
		$pstr = array();
		foreach($params as $name=>$value){
			if(trim($value)!=''){
				$pstr[] = "$name=$value";
			}
		}
		
		return $url.'?'.implode('&',$pstr);
	}
	
	
	protected function compileParam($set,$uriMatch){
		
		return preg_replace_callback('/\$(\d+)/',function($m)use($uriMatch){
				$group = $m[1];
				return arrayObj::getItem($uriMatch,$group);
			},$set);
			
	}

	

	
	/**
	 * 显示文件操作消息
	 * @param string  $msg 错误消息
	 * @param int $level  错误级别
	 */
	protected static function error($msg,$level){
		website::error($msg,$level,4);
	}
}
?>