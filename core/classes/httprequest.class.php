<?php
class HttpRequest extends model{
	
	protected $curl = '';
	
	protected $cookieFile = array();
	
	protected  $header = array();
	
	protected $returnHeader = false;
	
	protected $userAgent = '';
	
	protected  $follow = true;
	
	protected $params = array();
	
	protected $cookies = array();
	
	const METHOD_POST = 1;
	const METHOD_GET = 0;
	
	/**
	 * @param string  $url 请求地址
	 */
	public function  __construct($url){
		$this->curl = curl_init($url);
		$this->userAgent = arrayObj::getItem($_SERVER,'HTTP_USER_AGENT');
	}
	
	
	/**
	 * 设置请求头
	 * @param array $header
	 * @return httpRequest
	 */
	public function setHeader(array $header){
		$this->header = $header;
		return $this;
	}
	
	
	/**
	 * 设置请求是否返回响应头
	 * @param boolean $set 值 true/false
	 * @return httpRequest
	 */
	public function isReturnHeader($set){
		$this->returnHeader = (int)$set;
		return $this;
	}
	
	
	/**
	 * 设置请求的cookie保存的文件
	 * @param string $file 文件名
	 * @return httpRequest
	 */
	public function setCookieFile($file){
		$this->cookieFile = $file;
		return $this;
	}
	
	/**
	 * 设置请求的cookie
	 * @param string $file 文件名
	 * @return httpRequest
	 */
	public function setCookies(array $cookies){
	    $this->cookies = $cookies;
	    return $this;
	}
	
	
	/**
	 * 设置请求的user-agent，默认为浏览器的UA
	 * @param string $agent UA字符
	 * @return httpRequest
	 */
	public function setUserAgent($agent){
		$this->userAgent = $agent;
		return $this;
	}
	
	
	
	
	/**
	 * get请求
	 * @return mixed
	 */
	public function get(){
		return $this->execute();
	}
	
	/**
	 * post请求
	 * @return mixed
	 */
	public function post(){
		return $this->execute(self::METHOD_POST);
	}
	
	
	public function execute($method=self::METHOD_GET){
		
		$method = arrayObj::getRightVal($method, array(self::METHOD_GET,self::METHOD_POST), self::METHOD_GET);
		curl_setopt($this->curl,CURLOPT_USERAGENT,$this->userAgent);
		curl_setopt($this->curl, CURLOPT_HEADER, $this->returnHeader);
		curl_setopt($this->curl,CURLOPT_FOLLOWLOCATION,$this->follow);
		curl_setopt($this->curl, CURLOPT_POST, $method);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt ($this->curl, CURLOPT_RETURNTRANSFER, true); 
		
		
		if($this->cookieFile!=null){
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookieFile);
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookieFile);
		}
		
		if($this->params!=null){
			curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->params);
		}
		
		if($this->header!=null){
			
			$header = array();
			foreach($this->header as $k=>$h){
				$header[] = "$k: $h";
			}
			
			curl_setopt($this->curl, CURLOPT_HTTPHEADER, $header);
		}
		
		if($this->cookies!=null){
		    
		    $cookies = array();
		    foreach($this->cookies as $k=>$c){
		        $cookies[] = "$k=$c";
		    }
		    
		    curl_setopt($this->curl, CURLOPT_COOKIE, implode(';',$cookies) );
		    
		}
		
		
		$result = curl_exec($this->curl);
		return $result;
	}
	
	
	public function params(array $params=array()){
		$this->params = $params;
		return $this;
	}
	
	
	
	/**
	 * 是否跟踪重定向
	 * @param unknown $set
	 */
	public function followRedirect($set){
		$this->follow = $set;	
	}
	
	
	/**
	 * 获取请求后的头信息
	 * @return array
	 */
	public function getResponseHeader(){
		return curl_getinfo($this->curl);
	}
	
	public function __destruct(){
		curl_close($this->curl);
	}
	
	
}