<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * httpd响应函数
 * @author yhbhiron
 * @version 1.0.2
 * @since 2015
 *
 */
class httpd{
	
	protected static $headerSended = false;

	/**
	 * 301跳转
	 * @param string $url目录url
	 */
	public static function status301($url){
		if(headers_sent() || self::$headerSended == true){
			return;
		}
		
		self::$headerSended = true;
		header( "HTTP/1.1 301 Moved Permanently" );
		header( "Location: ".$url );	
			
	}
	
	public static function status304($replace=false){
		
		if(!$replace && (headers_sent() || self::$headerSended == true)){
			return;
		}
		
		self::$headerSended = true;
		header('HTTP/1.1 304 Not Modified');
		header('Status: 304 Not Modified');
			
	}	
	
	/**
	 * 404
	 */
	public static function status404($replace=false){
		
		if(!$replace && (headers_sent() || self::$headerSended == true)){
			return;
		}
		
		self::$headerSended = true;
		header('HTTP/1.1 404 Not Found'); 
		header("status: 404 Not Found"); 
		website::error('404 Not Found',2,7,404);
	}
	
	/**
	 * 500
	 */
	public static function status500($replace=false){
		
		if(!$replace && (headers_sent() || self::$headerSended == true)){
			return;
		}
		
		self::$headerSended = true;
		header('HTTP/1.1 500 Internal Server Error'); 
		header("status: 500 Internal Server Error"); 
		if(ErrorHandler::getErrors() == null){
			website::error('500 Internal Server Error',2,7,500);
		}
				
	}
	
	
	/**
	 * 403
	 */
	public static function status403($replace=false){
		
		if(!$replace && (headers_sent() || self::$headerSended == true)){
			return;
		}
		
		self::$headerSended = true;
		header('HTTP/1.1 403 Forbidden'); 
		header("status: 403 Forbidden"); 
		website::error('403  Forbidden',2,7,403);
		
	}
	
	
	/**
	 * 200
	 */
	public static function status200($replace=false){
		
		if(!$replace && (headers_sent() || self::$headerSended == true)){
			return;
		}
		
		self::$headerSended = true;
		header('HTTP/1.1 200 OK'); 
		header("status: 200 OK"); 
		
	}	
	
	/**
	 * 设置页面的输出Mime类型
	 * @param string $ext 页面扩展名:如jpg,png等
	 */
	public static function setMimeType($ext){
		
		$type = filer::getMimeType($ext);

		if(is_array($type)){
			$type = $type[0];
		}
		
		if($type != null){
			header('Content-type: '.$type.';charset='.website::$config['charset']);
		}
	}
	
	
	/**
	 * 设置下载文件头
	 * @param string $file 文件路径
	 * @param string $name 下载显示名称
	 */
	public static function setDownloadFile($file,$name){
		
		$name == preg_match('/msie/i',$_SERVER['HTTP_USER_AGENT']) ? urlencode($name) : $name;
		
		ob_end_clean();
		header("Content-type: application/octet-stream");
		$c =  file_get_contents($file);
		header('Content-Disposition: attachment; filename= '. $name.strrchr($file,'.') );
		header("Accept-Ranges: bytes");
	    header("Content-Length: ". strlen($c));
	    
	    exit($c);
		
	}
	
	
	/**
	 * 隐藏脚本使用的开发语言
	 */
	public static function hideServer(){
		header('X-Powered-By: Hiron 2.0');
	}
	
	
	
	public static function setCache($time=0){
		
		header('Date: '.time::gmtime());
		if($time>0){
			header('Cache-Control: max-age='.$time);
		}else{
			header('Cache-Control: no-cache');
		}
	}
	
	
	/**
	 * 重定向
	 * @param string $url重定向的url
	 * @param string $server 是否使用header
	 */
	public static function redirect($url,$server=true)
	{
		if($server){
			if(!@header("Location: $url")){
				exit("<script>window.location.href='$url'</script>");
			}
			exit;
		}else{
			exit("<script>window.location.href='$url'</script>");
		}
	}	
	
	
	public static function alert($msg,$exit=false){
		
		if($exit){
			exit('<script>alert("'.$msg.'");</script>');
		}
		
		echo ('<script>alert("'.$msg.'");</script>');
	}
	
	
	/**
	 *写入cookie,在执行完请求后，才能写入到cookie
	 *@param string $ckey cookie的key值
	 *@param string $cvalue 值
	 *@param int $expires 超时时间
	 *@return void
	 **/
	public static function setCookie($ckey,$cvalue,$expires=3,$delimer='~'){
		
		if(website::$env == website::ENV_CLI){
			return false;
		}
		
		$expires= !is_numeric($expires) ? 3:$expires;
		$cvalue = $cvalue.$delimer.self::cookieSalt($cvalue);
		$domain = arrayObj::getItem(website::$config,'cookie_domain',$_SERVER['HTTP_HOST']);
		setcookie($ckey,$cvalue,$expires<0 ? 0 : time()+$expires,'/',$domain);
	}
	
	
	/**
	 * 设置cookie，使用加验证的方式储存
	 * @param string $key cookie键名
	 * @param boolean $source 是否只读取源数据
	 * @param string $delimer 验证分隔符
	 * @return mixed/array
	 */
	public static function cookie($key,$source=false,$delimer='~'){
		
		$value = arrayObj::getItem($_COOKIE,$key);
		if($value == null){
			return $value;
		}
		
		if(is_array($value)){
			foreach($value as $k=>$v){
			    $crack = substr($v,strrpos($v,$delimer)+1);
			    $new = substr($v,0,strrpos($v,$delimer));
				$sign  = self::cookieSalt($new.arrayObj::getItem($_SERVER,'HTTP_USER_AGENT','nobot'));
				
				if($crack != $sign){
					$value = array();
					break;
				}
				
				$value[$k] = $source ? $v : $new;
			}
		}else{
			$svalue = $value; 
			$crack = substr($value,strrpos($value,$delimer)+1);
			$value = substr($value,0,strrpos($value,$delimer));
			$sign  = self::cookieSalt($value);
			
			if($crack != $sign){
				return null;
			}
			
			$value = $source ? $svalue : $value; 
		}
		

		
		return $value;
		
	}
	
	
	public static function cookieSalt($value){
		return StrObj::saltEncode($value.arrayObj::getItem($_SERVER,'HTTP_USER_AGENT','nobot'));
	}
	
	/**
	 * 清除cookie
	 * @param string $ckey cookie的键名
	 */
	public static function clearCookie($ckey){
		
		if(website::$env == website::ENV_CLI){
			return false;
		}
		
		if($ckey!=''){
			self::setCookie($ckey,'',0);
		}else{
			foreach($_COOKIE as $key=>$value){
				self::setCookie($key,'',0);
			}
		}
		
	}
	
}
?>