<?php
if(!defined('IN_WEB')){
	exit('Access Deny!');
}

/**
用户搜索类,用于sql搜索或只组建搜索相关的信息，如地址或条件
@author jackBrown
@version 2.0.2
@example

*/
class Search{
	
	/**排序,字段键值=>排序类型*/
	protected $order = array();
	
	/**保存请求的参数 array(req=>请求参数名,)*/
	protected $reqParam = array();
	
	/**
	 * 数据源
	 * @var query
	 */
	protected $source;
	
	
	/**地址*/
	public $url='';
	
	
	/**搜索条件*/
	protected $filter = array();
	
	
	/**排序*/
	protected $orders = array();
	
	
	/**条件的值不合法列表*/
	public $badVal = array();
	
	
	/**条件的值修改列表*/
	public $resetVal = array();
	
	
	
	
	/**
	 * @param string $s 数据源，一般为query,有时可以不填，主要获取地址就可以了
	 */
	public function __construct($s=null){
		
		if($s!=null && ($s instanceof query == false)){
			throw new Exception('数据库源必须为query');
			return false;
		}
				
		$this->source = $s;
		
	}
	
	/**
	 * 获取搜索相关的url
	 * @return array(all所有条件地址，no_order无排序的地址，order=>array()相关排序的地址)
	 */
	public function getURL(){
		
		if($this->url==''){
			return array();
		}
		
		
		/**无排序url**/
		$reqURL = array();
		$isVirtual = preg_match('/{.+?}/',$this->url);
		if(!$isVirtual){
			$newURL = StrObj::addNotHasStrR($this->url,'&','?');
		}else{
			$newURL = $this->url;
		}
		
		$reqURL['all'] = $reqURL['no_order'] = $newURL;
		$reqURL['order'] = array();
		$notComplieOrderURL = $newURL;
		
		if($this->reqParam!=null){
			
			$orderParams = array();
			$noOrderParams = array();
			
			if(!$isVirtual){
				$reqURL['all'] = $newURL.implode('&',array_map(function($v){ return $v['req'].'='.urlencode($v['val']); },$this->reqParam) );
			}			
			
			foreach($this->reqParam as $k=>$item){
				
				if($isVirtual){
					
					$reqURL['all'] = preg_replace_callback('/<'.$item['req'].':(.+?)>/is',function($m)use($item){
						$m[1] = str_replace('{'.$item['req'].'}',urlencode($item['val']),$m[1]);
						return $m[1];
					},$reqURL['all']);
					
				}
				
				if($item['type']=='cond'){
					
					$noOrderParams[] = $item;
					$notComplieOrderURL = preg_replace_callback('/<'.$item['req'].':(.+?)>/is',function($m)use($item){
						$m[1] = str_replace('{'.$item['req'].'}',urlencode($item['val']),$m[1]);
						return $m[1];
					},$notComplieOrderURL);
					
				}else if($item['type'] == 'order'){
					$orderParams[] = $item;
				}
				
				
			}
			
			
				
			if($isVirtual){
				foreach($noOrderParams as $k=>$item){
					
					$reqURL['no_order'] = preg_replace_callback('/<'.$item['req'].':(.+?)>/is',function($m)use($item){
						$m[1] = str_replace('{'.$item['req'].'}',$item['val'],$m[1]);
						return $m[1];
					},$reqURL['no_order']);
					
				}
			}else{
				$reqURL['no_order'] = $newURL.implode('&',array_map(function($v){ return $v['req'].'='.$v['val']; },$noOrderParams) );
			}


			if($orderParams!=null){
				
				foreach($orderParams as $k=>$item){
					
					if($isVirtual){
						$reqURL['no_order'] = preg_replace('/<'.$item['req'].':(.+?)>/is','',$reqURL['no_order']);		
					}else{
						$reqURL['order'][$item['req']] = StrObj::addNotHasStrR($reqURL['no_order'],'&','&').$item['req'].'='.$item['val'];
					}
				}

				if($isVirtual){
					foreach($orderParams as $k=>$item){
						foreach($orderParams as $j=>$item2){
							
							if(!isset($reqURL['order'][$item['req']])){
								$reqURL['order'][$item['req']] = $notComplieOrderURL;
							}
							
							if($item['req']!=$item2['req']){
								$reqURL['order'][$item['req']] = preg_replace('/<'.$item2['req'].':(.+?)>/is','',$reqURL['order'][$item['req']]);
							}else{
								$reqURL['order'][$item['req']] = preg_replace_callback('/<'.$item['req'].':(.+?)>/is',function($m)use($item){
									$m[1] = str_replace('{'.$item['req'].'}',$item['val'],$m[1]);
									return $m[1];
								},$reqURL['order'][$item['req']]);
							}
						}
					}
				}
						
			}
			
		}else{
			$reqURL['all'] = $this->url;
		}
		
		
		return $reqURL;
		
	}
	
	/**
	 * 获取搜索的数据源
	 */
	public  function getSource(){
		return $this->source;
	}
	
	/**
	 * 增加查询情况
	 * @param stirng $name 请求的字段名称
	 * @param callback $cond 处理请求时的回调 func($q查询query,$v 请求的值,$name 请求的键名,$search搜索对象)
	 * @return search
	 */
	public function addCase($name,$cond){
		
		$val = $this->toSafeKeyword( request::req($name) );
		if(!validate::isNotEmpty($val)){
			return $this;
		}
		
		if(is_callable($cond)){
			$this->source = call_user_func($cond,$this->source,$val,$name,$this);
		}else{
			return $this;
		}
		
		if(arrayObj::getItem($this->badVal,$name) == true){
			request::req($name,NULL);
			return $this;
		}
		
		$val = arrayObj::getItem($this->resetVal,$name,$val);
		$this->reqParam[$name] = array('req'=>$name,'type'=>'cond','val'=>$val);
		$this->filter[$name] = $val;
		return $this;
	}
	
	/**
	 * 获取搜索相关的当前查询条件
	 */
	public function getFilter(){
		return $this->filter;
	}
	
	
	/**
	 * 获取当关排序的参数
	 * @return array
	 */
	public function getOrders(){
        return $this->orders;	    
	}
	
	/**
	 * 添加一个排序条件
	 * @param string $name 请求的字段名
	 * @param array $range 排序值的列表 array(asc,desc,default)第一个值对应asc,第二个值对应desc,第三个值为默认值可选
	 * @param string $fld 排序的字段名，可选，默认同$name
	 * @return search
	 */
	public function addOrder($name,array $range,$fld=null){
		
		if($range == null && count($range)!=2){
			return $this;
		}
		
		$val = request::req($name);
		if(!validate::isNotEmpty($val) || !in_array($val,$range)){
			$val = arrayObj::getItem($range,2,$range[0]);
		}
		
		/**叠加数组，并加入到order中,用于query**/
		$this->order[$fld!=null ? $fld : $name] = $val == $range[0] ?  'asc' : 'desc';
		$this->source->orderby($this->order);
		
		$this->reqParam[$name] = array('req'=>$name,'type'=>'order','val'=>$val,'range'=>$range);
		$this->orders[$name] = $val;
		return $this;
	}
	
	
	/**
	 * 过滤搜索时会带来安全问题的字符
	 * @param string $kwyword
	 * @return string
	 */
	public function toSafeKeyword($keyword){
		
		if(is_array($keyword)){
			
			foreach($keyword as $k=>$w){
				$keyword[$k] = $this->toSafeKeyword($w);
			}
			
			return $keyword;
			
		}else{
			return StrObj::escape_string(preg_replace('/\s|\'|\"/','',urldecode($keyword)));
		}
	}

}


?>