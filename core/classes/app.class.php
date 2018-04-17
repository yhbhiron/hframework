<?php 
if(!defined('IN_WEB')){
	exit('Aceess Deny!');
}
/**
 * params参数说明,用于构造一个app时传入的参数
 * array(
	'def_act'=>'默认act'
	'template'=>'默认的视图文件，也可用$this->template修改',
	'items(相关请求参数的设置)' =>array(
		name=>array(
			'name'=>'对应模型或数据库的内部名称,必填',
			'gpc_name'=>'对应请求的名称，可选，默认为name的值',
			'gpc_type'=>'请求的方法：post,get,request,cookie,session',
			'type'=>'值的类型，可以是callback，或者指定一个方法名，或者对应validate的一个方法名，不存在的类型将会报错',
			'conv_type'=>'被转化后的类型，可以为php方法，或自定义的方法',
			'need=>array('add'=>true,'dd'=>false),
			'required'=>array('add'=>true,'update'=>true),
		),
	)
	
	required为false，无法判断这个值是否为该action需要验证的项，因此加了一个need来确定是否需要
	need为true时:表示这个参数是需要的，但不是必须
 * 
 * 
 * 
 * @depends template website system
 * @author: Jack Brown
 * @version: 1.0.5
 * @since: 2016-06-17
 * @example
 * $app = new app(array(
 * 
 * ));
 * 
 * $app->run()//不带act 默认执行默认路由，否则404
 * $app->run($act)
 * 
 */
class App extends Model{
		
		/**指定应用所需的参数*/
		protected  $items = array();
		
		/**应用名称*/
		protected  $appName = null;
		
		/**所需行为*/
		protected $needAct = array();
		
		/**应用的标题**/
		protected $actTitle = array('ajaxvalidate'=>'ajax验证');
		
		/**当前应用的行为**/
		protected $curAct  = '';
		
		
		/**应用中所需的变量*/
		protected $appVars = array();
		
		
		
		/**应用默认执行的操作*/
		protected $default='index';
		
		/**是否可执行对应的操作*/
		protected $canExecute = true;
		
		/**是否是api模式**/
		protected $apiMode = false;
		
		/**for debug 记录不能执行的字段名称**/
		protected $exceptItem = array();
		
		
		/**
		 * 当前应用不充许直接方问的行为
		 */
		protected $notAllowedDirectActions = array();
		
		
		/**
		 * @var system
		 */
		protected  $system;
		
		
		
		/**保存临时用的资源**/
		protected static $tmpSource = array();
		
		/**
		 * 模板引擎对象
		 * @var template 
		 * **/
		protected  $ui = null;
		
		
		/**
		 * 是否需要模板擎
		 * @var boolean
		 */
		protected $uiNeeded = true;
		
		
		
		/**相关显示的模板,只有当$ui存在时有效
		 */
		protected $template = '';
		
		/**
		 * 消息提示的模板
		 */
		protected $msgTpl = '';
		
		
		/**控制操作器地址信息**/
		protected $urls = array();
		
		
		/**
		 * 构造函数
		 * @param array $params 应用所需要参数配置
		 * @param string $alias 应用别名,默认为类别
		 */
		public function __construct($params=array()){

			$defParams = array();
			$defParams['items']   = array(
				array(
					'name'=>'item_name',
					'gpc_name'=>'item_name',
					'gpc_type'=>'post',
					'type'=>'isNotEmpty',
					'required'=>array('AjaxValidate'=>true),
					'error_msg'=>array('AjaxValidate'=>'验证的参数名称不正确'),
				),
				array(
					'name'=>'item_val',
					'gpc_name'=>'item_val',
					'gpc_type'=>'post',
					'type'=>'isNotEmpty',
					'need'=>array('AjaxValidate'=>true),
				),
				array(
					'name'=>'item_act',
					'gpc_name'=>'item_act',
					'gpc_type'=>'post',
					'type'=>'isNotEmpty',
					'required'=>array('AjaxValidate'=>true),
				)
				
			);
			
			if($params!=null){
				$params = arrayObj::extend($params,$defParams);
			}else{
				$params = $defParams;
			}
			
			if(validate::isNotEmpty(arrayObj::getItem($params,'items') )){
				$params['items'] = arrayObj::map($params['items'],function($k,$v,$new){
					$k = is_numeric($k) ? $v['name'] : $k;
					$new[$k] = $v;
					return $new;
				});
			}
			
					
			$this->items    = arrayObj::getItem($params,'items');		
			$this->template = $this->template!='' ?  $this->template : arrayObj::getItem($params,'template');
			$this->default  = arrayObj::getItem($params,'def_act',$this->default);
			$this->appName  = $this->appName!=null ? $this->appName : substr(get_class($this),0,-3);
			$this->actTitle = arrayObj::extend(arrayObj::getItem($params,'title'),$this->actTitle);
			$this->system   = $this->system();
			
			
			if($this->uiNeeded == true && $this->ui==null){
				$this->ui = new template();
			}
		}
		
		
		/**
		 * 执行应用的操作行为
		 * @param string $act 执行的行为，默认不指定，由请求参数def_act指定
		 * @param boolean $direct 是否为直接访问，默认为true 
		 * @return mixed 返回各行为的数据
		 */
		public function run($act=null,$direct=true){
			
			$t = website::curRunTime();
			if($act==null){
				$act = request::get('act')!='' ? request::get('act') :  $this->default;
			}
			
			/**直接访问权限判断*/
			if($direct && $this->notAllowedDirectActions!=null && in_array($act,$this->notAllowedDirectActions)){
				return false;
			}
			
			$act = strtolower($act);
			$actMethod = 'action'.ucfirst($act);
			if( method_exists($this,$actMethod)){
				
				$action = $this->curAct = $act;
				$vars = $this->exploreItem($act);
				$vars = $vars!=null ? $vars : arrayObj::getItem($this->appVars,$act,array());
				$this->beforeAction($actMethod,$vars);
				$data = call_user_func(array( $this,$actMethod),$vars);
								
			}else{
				
				/**加载单个行为文件模式**/
				$actClsName = 'Action'.get_class($this).ucfirst($act);
				if(class_exists($actClsName,true)){
					
					$this->curAct = $act;
					$actCls = new $actClsName($this);
					$vars = $this->exploreItem($act);
					$vars = $vars!=null ? $vars : arrayObj::getItem($this->appVars,$act,array());
					$this->beforeAction($act,$vars);
					$data = $actCls->execute($vars);
					
				}else{
					httpd::status404();
					return false;
				}
				
			}
			
			$afterData = $this->afterAction($action,$data,$vars);
			if($afterData!=null){
			    $data = $afterData;
			}
			
			if($this->ui instanceof  template){
				
				/**指定模板情况下，显示模板**/
				if($this->template!=null){
					
					/**当前应用行为**/
					$this->ui->assign('act',$this->curAct);
					$this->ui->assign('acturl',$this->getActURL());
					$this->ui->display($this->template);
					
				}
				
			}
			
			
			website::debugAdd('运行应用'.$this->appName.';action:'.$this->curAct,$t);
			if(website::$responseType == 'json' && $direct==true){
			    
			    $default = array(
			        'error'=>0,
			        'msg'=>'ok'
			    );
			    
			    $data = arrayObj::extend($data, $default);
				die( json_encode($data) );
				
			}
			
			return $data;
		}
		
		
		/**
		 * 获取应用的参数配置
		 */
		public function getItems(){
			return $this->items;
		}
		
		
		/**
		 * 获取当前执行的action
		 */
		public function getCurAction(){
			return $this->curAct;
		}
		
		
		/**
		 * 获取当前执行的action
		 */
		public function getAppName(){
			return $this->appName;
		}		
		
		/**
		 * 行为执行前做点什么
		 * @param string $action action的对外名称
		 * @param array $vars action的相关使用的参数
		 */
		protected function beforeAction($action,$vars){
			
			
		}
		
		
		/**
		 * 行为执行后做点什么,如果返回有数据，将替换
		 * action返回的数据
		 * @param string $action action的对外名称
		 * @param mixed $data 行为执行返回的结果
		 * @param mixed $vars action的相关使用的参数
		 */
		protected function afterAction($action,$data,$vars){
			
		}
		
		
		/**
		 * 获取当前控制器的相关行为
		 */
		protected function getActions(){
			
			if($this->needAct!=null){
				return $this->needAct;
			}
			
			$methods = get_class_methods($this);
			return $this->needAct = array_filter($methods,function($v){ return strtolower(substr($v,0,6)) == 'action'; });
		}
		
		
		/**
		 * ajax请求验证数据的合法性，返回数据给客户端
		 * @param array $vars
		 */
		protected function actionAjaxValidate($vars){

			
			if(!$this->canExcute){
				die( json_encode( array('error'=>'1','msg'=>'参数错误！') ));
			}
			
			$items = $this->items;
			if($items==null){
				die( json_encode( array('error'=>'0','msg'=>'无需检查!') ));
			}
			
			foreach($items as $k=>$item){
				
				$reqired = arrayObj::getItem($item,'required');
				$name    = arrayObj::getItem($item,'gpc_name',$item['name']);
				$val     = arrayObj::getItem($vars,'item_val');
				if($name == $vars['item_name']){
					
					$isReq = arrayObj::getItem($reqired,$vars['item_act']) == true;
					$err   = StrObj::def(arrayObj::getItem(arrayObj::getItem($item,'error_msg'),$vars['item_act']),'参数值不正确');
					$type  = arrayObj::getItem($item,'type');
					
					if($isReq){
						
						if(validate::isNotEmpty($val) == false){
							die( json_encode( array('error'=>'1','msg'=>$err,'item_obj'=>$name) ));
						}else{
							if(!$this->validVars($val,$type)){
								die( json_encode( array('error'=>'1','msg'=>$err,'item_obj'=>$name) ));
							}
						}
						
					}else{
						
						if(validate::isNotEmpty($val) == true){
							
							if(!$this->validVars($val,$type)){
								die( json_encode( array('error'=>'1','msg'=>$err,'item_obj'=>$name) ));
							}
							
						}
					}
					
				}
				
			}
			
			die( json_encode( array('error'=>'0','msg'=>'正确','item_obj'=>$name) ));
		}
		
		
		/**
		 * 创建一个前端的js验证代码，主要是依据app.hiron.js的规则来实现
		 * @return string 验证规则json
		 */
		protected  function createValidate(){
			
				if($this->items == NULL){
					return '[]';
				}
				
				$jsItems = array();
				$needAct = $this->getActions();
				
				if($needAct == null){
					return '[]';
				}
				
				foreach($this->items as $k=>$item){
					
					if(arrayObj::getItem($item,'fixed_value')!=''){
						continue;
					}
					
					$temp   = array();
					$temp['name'] = '*[name="'.arrayObj::getItem($item,'gpc_name',$item['name']).'"]';	
					if(is_array($item['type'])){
						
						$funcStr = array();
						foreach ($item['type'] as $m=>$n){
							if(is_numeric($m)){
								$funcStr[]="validate.$n(val)";
							}else{
								$pstr = implode(',',$n);
								$pstr = $pstr!='' ? ','.$pstr : $pstr;
								$funcStr[]="validate.$m(val$pstr)";
							}
						}
						
						$funcStrOut = implode(' && ',$funcStr);
						$temp['type'] = '
							function(val){
								'.$funcStrOut.';
							}
						';	
					}else{
						$temp['type']     = 'validate.'.$item['type'];
					}
					
					$acts = arrayObj::getItem($item,'required');
					if($acts!=null){
						foreach($acts as $a=>$v){
							$temp['required'][$a] = $v;
						}
					}
					
					$acts = arrayObj::getItem($item,'need');
					if($acts!=null){
						foreach($acts as $a=>$v){
							$temp['required'][$a] = false;
						}
					}
					
					$msgs = arrayObj::getItem($item,'error_msg');
					if($msgs!=null){
						
						$err   = StrObj::def(current($msgs),'参数值不正确');
						$temp['err_msg'] = $err;
					}else{
						$temp['err_msg'] = '参数不正确';
					}
					
					$temp['none_msg'] = $temp['err_msg'];
					
					$msgs = arrayObj::getItem($item,'ok_msg');
					if($msgs!=null){
						$err   = StrObj::def(current($msgs),'');
						$temp['ok_msg'] = $err;
					}else{
						$temp['ok_msg'] = '';
					}
						
					$jsItems[$item['name']] = $temp;
						
				}
				
			
				$json =  json_encode($jsItems);
				$json = preg_replace_callback('/\"type\":\"([^\"]+)\"/',function($m){
					$m[1] =str_replace(array('\r','\n','\t'),'',$m[1]);
					return 'type:'.$m[1];
				},$json);
				
				return $json;
		}
		
		
		/**
		 *  创建form表单的测试
		 * @param string $act 指定的行为
		 * @return string
		 */
		public function formTest($act){
			
			if($this->items == NULL){
				return '';
			}

			
			$items = array_filter($this->items,function($v)use($act){
				
				$need     =  arrayObj::getItem($v,'need');
				$required =  arrayObj::getItem($v,'required');
				return arrayObj::getItem($need,$act) == true || arrayObj::getItem($required,$act) == true;
				
			});
			
			
			$fields = '';
			$hasPost = false;
			$hasFile = false;
			foreach($items as $k=>$cnf){
				
				$name = arrayObj::getItem($cnf,'gpc_name',$cnf['name']);
				$type = arrayObj::getItem($cnf,'type') == 'uploadImage' ? 'file' : 'text';
				if(isset($cnf['allow_array'])){
					$is_array =  is_array($cnf['allow_array']) ? arrayObj::getItem($cnf['allow_array'],$cnf['name'],false) : $cnf['allow_array'];
				}else{
					$is_array = false;
				}
				
				if($is_array){
					$name.='[]';
				}
				
				$input  = '<input type="'.$type.'" name="'.$name.'" size=100 />';
				$fields.='<p><label>'.$name.':</label>'.$input.'</p>';
				
				if(arrayObj::getItem($cnf,'gpc_type') == 'post'){
					$hasPost = true;
				}
				
				if(arrayObj::getItem($cnf,'gpc_type') == 'file'){
					$hasFile = true;
				}
				
			}
			
			$act = arrayObj::getItem(arrayObj::getItem($this->getActURL(),$act),'url');
			$form = '<form target="_blank" method="'.($hasPost ? 'post' : 'true').'" action="'.$act.'" '.($hasFile ? 'enctype="multipart/form-data"' : '').'>'
			.$fields
			.'<input type="submit" value="测试" />'
			.'</form>';
							
			return $form;
		}
		
		/**
		 * 获取对应操作的对应的地址
		 * @return array
		 */
		protected function getActURL(){
			
			$this->needAct = $this->getActions();
			if($this->needAct == null){
				return false;
			}
			
			if($this->urls!=null){
				return $this->urls;
			}

			$urls = array();
			foreach($this->needAct as $k=>$act){
				$act = strtolower(substr($act,6));
				$urls[$act]['route_key'] = strtolower(StrObj::delimerClassName($this->appName).'_'.$act);
				$urls[$act]['url']   = website::$route ? website::$route->getURL($urls[$act]['route_key'],lcfirst($this->appName),$act) : '';
				$urls[$act]['title'] = arrayObj::getItem($this->actTitle,$act); 
			}

			return $urls;
		}
		
		
		/**
		 * 遍历变量合法性
		 * @param string $actName 执行操作的名称
		 */
		protected function exploreItem($actName){
			
			if($this->items == null){
				return false;
			}
			
			if(arrayObj::getItem($this->appVars,$actName)!=null){
				return $this->appVars[$actName];
			}
			
			
			$this->appVars[$actName]= array();
			foreach($this->items as $item){
				if(isset($item['name'])){
				
					if(arrayObj::getItem($item,'name')!='' && arrayObj::getItem($item,'type')!=''){
						
						$temp     = '';
						$type     = $item['type'];
						$require  = arrayObj::getItem(arrayObj::getItem($item,'required',array()),$actName,false);
						$need     = arrayObj::getItem(arrayObj::getItem($item,'need',array()),$actName,false);
						
						if(isset($item['allow_array'])){
							$array    =  is_array($item['allow_array']) ? arrayObj::getItem($item['allow_array'],$actName,false) : $item['allow_array'];
						}else{
							$array = false;
						}
						
						$gpc = arrayObj::getItem($item,'gpc_name',$item['name']);
						
						if(arrayObj::getItem($item,'fixed_value')!=''){
						
							$temp = $item['fixed_value'];
							
						}else if($gpc!=''){
							
							
							if(arrayObj::getItem($item,'gpc_type')=='get'){
								$temp = request::get($gpc);
							}else if(arrayObj::getItem($item,'gpc_type')=='post'){
								$temp = request::post($gpc);
							}else if(arrayObj::getItem($item,'gpc_type')=='cookie'){
								$temp = request::cookie($gpc);
							}else if(arrayObj::getItem($item,'gpc_type')=='session'){
								$temp = session::get($gpc);
							}else if(arrayObj::getItem($item,'gpc_type')=='request'){
								$temp = request::req($gpc);
							}
							
						}
						
						if($item['gpc_type']!='file'){
							
							/**默认值**/
							$temp = !validate::isNotEmpty($temp) && ($need || $require) && isset($item['default']) ?  $item['default'] : $temp;
							
							if($this->_validate($temp,$type,$require,$need,$array)){
								
								/**必填或需要时写入参数组*/
								if($require || ( (!$require || $need) && validate::isNotEmpty($temp)) ){
									$temp = $this->convItemVar($temp,$item);
									$this->appVars[$actName][$item['name']] = $temp;
								}else{
									($require ||  $need) && $this->appVars[$actName][$item['name']] = null;
								}
																	
							}else{
								
								if($require ||  $need ){
									
									$this->exceptItem[] = $item['name'];
									$this->canExecute = false;
									$msg = arrayObj::getItem( arrayObj::getItem($item,'error_msg'), $actName,$actName.';'.$item['name'].'数据格式不正确！');
									
									if(request::isAjax() == false){
										website::debugWarning($msg);
									}else{
										$this->error($msg);
									}
								}
							}
						
						}
						
						
						/**上传文件处理**/
						if($item['gpc_type'] == 'file' && $this->canExecute ){
							
							if(is_array($item['type'])){
								filer::$allowFormat = $item['type'];
							}else if($item['type'] == 'uploadImage'){
								filer::$allowFormat = array('jpg','jpeg','png','gif');
							}else if($item['type'] == 'uploadFile'){
								filer::$allowFormat = array('txt','xls','xlsx','doc','docx','pdf','csv');
							}else if($item['type'] == 'uploadVideo'){
								filer::$allowFormat = array('mp4','wav','mp3','mpeg','wmv','avi','rm','rmvb');
							}else if($item['type'] == 'allFile'){
								filer::$allowFormat = null;
							}
							
							filer::$uploadSizeLimit = arrayObj::getItem($item,'size_limit',0);
							
							$uploadIsArray = isset($_FILES[$gpc]['size']) && is_array($_FILES[$gpc]['size']) && array_sum($_FILES[$gpc]['size'])>0;
							$postedFile = (isset($_FILES[$gpc]['size']) && !is_array($_FILES[$gpc]['size']) && $_FILES[$gpc]['size']>0) || $uploadIsArray;
							
							/**上传数组检测**/
							if(!$array && $uploadIsArray ){
								$this->exceptItem[] = $item['name'];
								$this->canExecute = false;
								continue;
							}
							
							if( ( isset($_FILES[$gpc]['error'][0]) && $_FILES[$gpc]['error'][0] == 2) 
								|| (isset($_FILES[$gpc]['error']) && $_FILES[$gpc]['error'] == 2) ){
								$this->showMsg('上传文件过大',504);
							}
							
							if($require){
								
								if($postedFile){
									
									$temp = filer::upload($gpc,website::$config['upload_dir'].'/temp/');
									if($temp == null ){
										
										$this->exceptItem[] = $item['name'];
										$this->canExecute = false;
										
									}else{
										
										/**将文件加入到回收数组中**/
										if(arrayObj::getItem($temp,'save_path')==null)
										{
											foreach($temp as $k=>$upfile){
												self::$tmpSource[] = $upfile['abs_path'];
											}
		
											
										}else{
											
											self::$tmpSource[] = $temp['abs_path'];;
										}
										
										$this->appVars[$actName][$item['name']] = $temp;
										
									}
								}else{
									$this->exceptItem[] = $item['name'];
									$this->canExecute = false;
								}
							}else{
								
								if( $need && $postedFile) {
									
									$temp = filer::upload($gpc,website::$config['upload_dir'].'/temp/');
									if($temp == null ){
										
										$this->exceptItem[] = $item['name'];
										$this->canExecute = false;
										
									}else{
										
										/**将文件加入到回收数组中**/
										if(arrayObj::getItem($temp,'save_path')==null)
										{
											foreach($temp as $k=>$upfile){
												self::$tmpSource[] = $upfile['abs_path'];;
											}
		
											
										}else{
											
											self::$tmpSource[] = $temp['abs_path'];
										}
										
										$this->appVars[$actName][$item['name']] = $temp;
										
									}
								}
								
							}
							//eof if file required
						}
						//eof is file
						
						
					}
				}//eof items foreach
			}
			
			website::debugWarning('应用'.$this->appName.'意外字段：'.var_export($this->exceptItem,true));
			return $this->appVars[$actName];
			
		}
		
		/**
		 * 验证一个参数的值是否合法
		 * @param mixed $val 值
		 * @param mixed $type 要求类型函数或者方法
		 * @param boolean $require 是否必填
		 * @param boolean $need 是否需要
		 * @param boolean $allowArray 是否可以为一个数组
		 * @return boolean
		 */
		private function _validate($val,$type,$require,$need,$allowArray){
			
			/**不是必填，也不是需要的，不验证，表示验证通过*/
			if(!$require && !$need){
				return true;
			}
			
			/**如果没有值，但又是必须的，不能通过*/
			if(!validate::isNotEmpty($val) && $require){
				return false;
			}
			
			/**不是必填，是需要的，但没有值，不验证，验证通过*/
			if((!$require && $need &&  !validate::isNotEmpty($val)) ){
				return true;
			}
			
			/**如果是数组，不允许数组，不通过*/
			if(is_array($val) && $allowArray == false){
				return false;
			}
					
			/**是否为数组变量**/
			if(is_array($val)){
			
				foreach($val as $k=>$var){	
									
					if(!$this->validVars($var,$type) && $require){
						return  false;
					}else if(!$require && $need && validate::isNotEmpty($var) && !$this->validVars($var,$type) ){
						return false;
					}
	
				}
					
				
			}else{
	
				if($require && !$this->validVars($val,$type)){
					return false;
				}else if($need &&  validate::isNotEmpty($val) && !$this->validVars($val,$type) ){
					return false;
				}
			}	

			
			return true;
			
		}
		
		/**
		 * 转换变量
		 * @param string mixed $var 变量值
		 * @param array $itemInfo 变量信息
		 * @return mixed $var 转化后的变量
		 */
		protected function convItemVar($var,$itemInfo){
			
			if($var == null || $itemInfo == null){
				return $var;
			}
			
			/**数组情况**/
			if(is_array($var)){
				
				foreach($var as $k=>$v){
					$var[$k] = $this->convItemVar($v,$itemInfo);
				}
				
				return $var;
			}
			
			
			$var = arrayObj::getItem($itemInfo,'no_trim')   === true ? $var : trim($var);
			if( arrayObj::getItem($itemInfo,'has_html') == false){
				
				$hasQuote = arrayObj::getItem($itemInfo,'has_quote') == true;
				$var =  $hasQuote ? $var : preg_replace('/\"|\'/','',stripslashes($var));
				$var =  $hasQuote ? $var : preg_replace('/\s|\n|\r/','',$var);
				$var = StrObj::toSafeHtml($var);
				
			}
			
			
			
			$convFunc = arrayObj::getItem($itemInfo,'conv_type');
			if($convFunc!=null){
				
				if(is_array($convFunc)){
					foreach($convFunc as $k=>$func){
						if(is_callable($func)){
						    $var = call_user_func($func,$var);
						}else if(method_exists('StrObj',$func)){
							$var = StrObj::$func($var);
						}else if(method_exists('Number',$func)){
							$var = number::$func($var);
						}else if(method_exists('ArrayObj',$func)){
							$var = arrayObj::$func($var);
						}
					}
				}else{
					
					if(is_callable($convFunc)){
						$var = $convFunc($var);
					}else if(method_exists('StrObj',$convFunc)){
						$var = StrObj::$convFunc($var);
					}else if(method_exists('Number',$convFunc)){
						$var = number::$convFunc($var);
					}else if(method_exists('ArrayObj',$convFunc)){
						$var = arrayObj::$convFunc($var);
					}
					
				}
				
			}
			
		
			
			return $var;
		}
		
		/**
		 * 验证用户单个数据项的合法性,支持validate验证
		 * @param mixed $type string/array,变量类型,array('type'=>array(类型方法名=>array(参数1,参数2))
		 * @param mixed $var
		 * @return true/false
		 */
		protected function validVars($var,$type){
			
			if(is_array($type)){
				
				$res = true;
				foreach($type as $method=>$params){
					
					/**将第一个参数加入设置为值$var*/
					if(is_callable( array('validate',$method)) ){
						array_unshift($params,$var);
						$res = $res && call_user_func_array(array('validate',$method),$params);
					}else if(is_callable($method)){
						array_unshift($params,$var);
						$res = $res && call_user_func_array($method,$params);
					}else if(is_callable(array('validate',$params))){
						$res = $res && call_user_func(array('validate',$params),$var);
					}else if(is_callable($params)){
						$res = $res && call_user_func($params,$var);
					}else{
						$res = false;
					}
				}
				
				return $res;
				
			}else{
				
				if(is_callable( array('validate',$type)) ){
					return validate::$type($var);
				}else if(is_callable($type)){
					return call_user_func($type,$var);
				}
			}
			
			return false;
		}
		
		/**
		 * 清空临时资源,主要是临时文件
		 */
		public static  function recycle(){
			
			if(self::$tmpSource == null){
				return false;
			}
			

			foreach(self::$tmpSource as $k=>$source){
				
				if(filer::isFullPath($source) == false){
					$source = APP_PATH.$source;
				}
				
				if(is_file($source)){
					@unlink($source);
				}
			}
			
			website::debugAdd('回收应用资源！');
			self::$tmpSource = null;
			
		}
		
		
		/**
		 * 格式化数据格式
		 * @param mixed $data
		 * @return mixed
		 */
		public static function dataFormat($data,$format=array(),$name=''){
		    
		    
		    if(is_array($data)){
		        foreach($data as $k=>$v){
		            $data[$k] = self::dataFormat($v,$format,$k);
		        }
		    }else{
		        
		        $func = ArrayObj::getItem($format,$name);
		        if(is_callable($func)){
		            $data = call_user_func($func,$data);
		        }else if(method_exists('StrObj',$func)){
		            $data = StrObj::$func($data);
		        }else if(method_exists('Number',$func)){
		            $data = number::$func($data);
		        }else if(method_exists('ArrayObj',$func)){
		            $data = arrayObj::$func($data);
		        }else{
		            
		            if(is_numeric($data)){
		                
		                if(strstr($data,'.')){
		                    $data = floatval($data);
		                }else{
		                    $data = intval($data);
		                }
		                
		            }else if(is_bool($data)){
		                $data = $data == true ? 1 : 0;
		            }else if(is_null($data) || strval($data) == null){
		                $data = '';
		            }
		            
		        }
		        
		    }
		    
		    
		    return $data;
		    
		}
		
		/**
		 * 将资源添加到临时回收站
		 * @param string $file文件件
		 */
		protected function addToRecycle($file){
			
			if($file == null){
				return false;
			}
			
			if(is_array($file)){
				self::$tmpSource =self::$tmpSource!=null ? array_merge(self::$tmpSource,$file) : $file;
			}else{
				self::$tmpSource[] =$file;
			}
			
		}
		
		
		/**
		 * 设置获取的模型
		 * @param string $v 值
		 * @return ORM
		 */
		protected function model($v=null){
			
		}
		
		
		/**
		 * 创建一个参数设置配置
		 */
		protected function createItems(){
			
			$model = $this->model();
			if(!($model instanceof ORM) && !($model instanceof Annotation)){
				return array();
			}
			
			$typeName = array(
				'string'=>'isNotEmpty',
				'int'=>'isInt',
				'float'=>'is_float',
			);
			
			$rules = $model->validates();
			$items = array();
			foreach($rules as $name=>$rule){
				$items[$name] = array(
					'name'=>$name,
					'gpc_type'=>'post',
					'type'=>arrayObj::getItem($typeName,$rule['type'],'isNotEmpty'),
					'required'=>array()
				);
			}
			
			return $items;
		}
		
		/**
		 * 判断当前行为指定行为时
		 * @param array $actions 行为列表
		 * @param boolean $canExecute 是否可以执行
		 * @return boolean
		 */
		protected function onActions(array $actions,$canExecute=true){
			
			if(in_array($this->curAct,$actions)){
				return $canExecute ? $this->canExecute : true;
			}
			
			return false;
		}
		
		
		
		/**
		异常报告
		@param string $exception 异常
		*/
		protected function error($exception){

			website::error('应用：'.get_class($this).';'.$exception,2);

		}

		/**
		 * 展示消息
		 * @param string $msg 提示信息
		 * @param int $code 消息状态：大于零为有错误
		 */
		public  function showMsg($msg,$code=0,$respType=null){
			
			if(Website::$responseType == 'json' || $respType == Website::RESP_TYPE_JSON){

			    Website::$responseType =  Website::RESP_TYPE_JSON;
				die(json_encode(
					array(
						'msg'=>$msg,
						'code'=>$code,
						'error'=>$code,
					)
				));
				
			}else{
				if($this->msgTpl!=''){
					$this->ui->assign('app_send_msg',$msg);
					$this->ui->assign('app_send_code',$code);
					$this->ui->display($this->msgTpl);
				}else{
					exit($msg);
				}
			}
		}

	}

?>