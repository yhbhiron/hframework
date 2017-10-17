<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * @depends
 * 
 **/
class document extends reflectorExt{
	
	protected $classComment = '';
	
	protected static $defRenderFile = '';
	
	
	public function __construct($cls){
		
		parent::__construct($cls);
		$this->classComment = $this->getClassComment();
		
		if(self::$defRenderFile==''){
			
			$config = $this->system()->loadConfig('doc');
			self::$defRenderFile = arrayObj::getItem($config,'tpl_file');
			
		}
		
	}
	

	public function getClsDepends(){
		return $this->getDocElement('depends',$this->classComment);
	}
	
	public function getClsName(){
		return StrObj::def($this->getDocElement('name',$this->classComment),$this->getName());
	}
		
	public function getClsDesc(){
		return htmlspecialchars($this->getDesc($this->classComment));
	}
	
	
	public function getClsExample(){
		return htmlspecialchars(StrObj::def($this->getDocElement('example',$this->classComment),''));
	}
	
	public function getClsVersion(){
		return StrObj::def($this->getDocElement('version',$this->classComment),'');
	}

	public function getClsAuthor(){
		return StrObj::def($this->getDocElement('author',$this->classComment),'');
	}
	
	public function getClsIsAPI(){
		return $this->getDocElement('is_api',$this->classComment)  == 'true';
	}
	
	public function getClsParentClass(){
		
		$p = $this->getParentClass();
		if($p){
			return	$p->getName();
		}
		
		return '';
	}
	
	
	public function getMethodDoc(){
		
		$list = $this->getMethodsCommentList();
		if($list==null){
			return array();
		}
		
		ksort($list);
		
		$return = array();
		foreach($list as $name=>$c){
			
			$m = $this->getMethod($name);
			$doc = array();
		      
			/**有api_url的情况下为api的文档*/
			$doc['is_api'] = $this->getDocElement('is_api',$c) == 'true';
			$doc['api_url'] = $this->getDocElement('api_url',$c);
			$doc['api_return_param'] = $this->getParamsInfo($c,null,'api_return_param');
			$doc['api_req_param'] = $this->getParamsInfo($c,null,'api_req_param');
			$doc['api_method'] = $this->getDocElement('api_method',$c);
			$doc['api_return_type'] = $this->getDocElement('api_return_type',$c);
			$doc['api_error_codes'] = $this->getApiErrorCodes($c);
			
			
			$doc['name'] = $this->getDocElement('name',$c);
			$doc['author'] = $this->getDocElement('author',$c);
			$doc['version'] = $this->getDocElement('version',$c);
			$doc['desc'] = htmlspecialchars($this->getDesc($c));
			$doc['params'] = $this->getParamsInfo($c,$m);
			$doc['static'] = $m->isStatic();
			$doc['abstract'] = $m->isAbstract();
			$doc['private'] = $m->isPrivate();
			$doc['public'] = $m->isPublic();
			$doc['protected'] = $m->isProtected();
			$doc['method_obj'] = $c;
			$doc['return'] = $this->getDocElement('return',$c);
			
			$return[$name] = $doc;
			
		}
		
		return $return;
	}
	
	
	public function render($tpl=''){
		
		$tpl = StrObj::def($tpl,self::$defRenderFile);
		$t = new template(true);
		$t->assign('doc',$this);
		$code = $t->fetchFile($tpl);
		return $code;		
	}
	
	protected function getDocElement($name,$comment,$multi=false){
		
		if(trim($comment) == ''){
			return;
		}
		
		$pattern = '/@'.$name.'\s+([\s\S]+?)(?=\n\s*\*+\/|\s*\n\s*\**\s*@)/is';
		if($multi == false){
			if(!preg_match($pattern,$comment,$match)){
				return;
			}
			
			return preg_replace('/\*/','',trim($match[1]));
		}else{
			
			if(!preg_match_all($pattern,$comment,$match)){
				return array();
			}
			
			
			foreach($match[1] as $k=>$v){
				$match[1][$k] = preg_replace('/\*/','',trim($v));
			}
			
			return $match[1];
		}
	}
	
	
	protected function getDesc($comment){
		
		if(!preg_match('/^\/\s*\*+([\S\s]+?)(?=\n\s*\*+\/|\n\s*\**\s*@)/is',$comment,$match)){
			return;
		}

		return preg_replace('/\n|\*/','',trim($match[1]));
	}	
	
	
	/**
	 * 获取方法的全部可能的参数
	 * @param string $desc 注释
	 * @param refectionMethod $method 方法反射对象
	 * @return array
	 */
	protected function getParamsInfo($desc,$method,$pkey='param'){
		
	    $params = $this->getDocElement($pkey,$desc,true);
		$info = array();
		$sourceInfo = array();
		if($method != null){
    		$sourceInfo = $this->getMethodParams($method);
		}
		
		if(validate::isNotEmpty($params) == false){
		    return $sourceInfo;
		}

		foreach($params as $k=>$p){
			
			$temp = preg_split('/\s+/',$p);
			$peach = array();
			
			if($temp == null){
				continue;
			}
			
			$num = count($temp);
			if($num == 1){
			    
				$peach['name'] = $temp[0];
				$peach['type'] = '';
				$peach['desc'] = '';
				
			}else if($num == 2){
			    
				$isvar = preg_match('/^\$[a-z_0-9]+$/i',$temp[1]);
				$peach['name'] = $isvar ? $temp[1] : $temp[0];
				$peach['type'] = $isvar ? $temp[0] : '';
				$peach['desc'] = $isvar ? '' :  $temp[1];
				
			}else if($num>=3){
			    
			    /** type name desc optional default **/
				$peach['name'] = $temp[1];
				$peach['type'] = $temp[0];
				$peach['desc'] = $temp[2];
				$peach['optional'] =  arrayObj::getItem($temp,3);
			    $peach['default'] = arrayObj::getItem($temp,4);
				
				
			}
			
			$peach['name'] = trim($peach['name'],'$');
			
			$info[$peach['name']] = $peach;
		}
		
		return arrayObj::extend($info,$sourceInfo);
		
	}
	
	/**
	 * 获取方法原代码中非注释的参数信息
	 * @param unknown_type $method
	 */
	protected function getMethodParams($method){
		
		$params = $method->getParameters();
		$list = array();
		
		if($params == null){
			return array();
		}
		
		foreach($params as $k=>$p){
			
			$name = $p->getName();
			$list[$name] = array(
				'name'=>$p->getName(),
				#'type'=>$p->getType(),
				#'byval'=>$p->canBePassedByValue(),
				'optional'=>$p->isOptional(),
			);
			
			if($list[$name]['optional']){
			    try{
				    $list[$name]['default'] = strval(@$p->getDefaultValue());
			    }catch(Exception $e){
			        
			    }
			}else{
				$list[$name]['default'] = '';
			}
		}	
		
		return $list;
	}

	
	protected  function getApiErrorCodes($comments){
	    
	    $list = $this->getDocElement('api_error_code', $comments,true);
	    if($list == null){
	        return $list;
	    }
	    
	    $return = array();
	    foreach($list as $k=>$e){
	        
	        $temp = preg_split('/\s+/',$e);
	        if($temp == null){
	            continue;
	        }
	        
	        $return[] = array('code'=>arrayObj::getItem($temp,0),'desc'=>arrayObj::getItem($temp,1));
	        
	    }
	    
	    
	    return $return;
	    
	}
	
}