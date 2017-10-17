<?php
if(!defined('IN_WEB')){
	exit('Access Deny!');
}

/**
数据库映射
任何信息都分:增 删 改 查,其它信息类以他为基类
或者其它类由多个模型组合而成
@depends db,StrObj,arrayObj
@since 2014-03-11
@author Jack Brown
@version 1.4.2
*@example
*$user = ORM::factory('user');
*$user->add(array('name'=>'yhb'));
*$user= ORM::factory('user',12);
*echo $user->user_name;
*/
class ORM extends model{
	
	/*模型表名*/
	protected $modTable;
	
	/*模型唯一标识名*/
	protected $modKey='id';
	
	
	protected $modItems = array();
	
	
	/*保存表的结构,减少重复获取*/
	protected static $hasSavedItem = array();
	
	
	/**相关验证规则*/
	protected static $modValidate = array();
	
	
	/**是否加载了数据*/
	protected  $loaded = false;
	
	/**是否查找过*/
	protected $finded = false;
	
	/**数据库连接器*/
	protected $db;
	
	/**数据库名称*/
	protected $db_key = null;
	
	/**模型的数组数据*/
	protected $modDatas = array();
	
	/**数据更新后，改变了的项和值*/
	protected $changedDatas = array();
	
	/**
	 * 信息唯一确定的值
	 */
	protected $modVal;
	
	
	/**数据驱动支持列表*/
	protected $supportDbDriver = array('mysql');
	
	
	/**
	 * 构造
	*/
	public function __construct($mod='',$value='',$key=''){
		
		$isModel =  strtolower(substr(get_class($this),-5)) == 'model';
		
		/**模型中不能通过构造指定*/
		if($isModel){
			$this->modTable = $this->modTable!='' ? $this->modTable : substr(get_class($this),0,-5);
			$this->modVal = func_get_arg(0);
		}else{
			$this->modTable = $mod!='' ? $mod : ($this->modTable!='' ? $this->modTable : substr(get_class($this),0,-5));
			$this->modKey   = $key!='' ? $key : $this->modKey;
			$this->modVal   = $value;
			
		}
		
		$this->init();
	}
	
	
	/**
	 * 创建模型
	 * @param string $model 模型名
	 * @param string $value 模型值
	 * @return ORM
	 */
	public static function factory($model,$value='',$key=null){
		
		$className = StrObj::getClassName($model).'Model';
		!class_exists($className,false) && website::loadClass('models.'.$model);
		  
		if(!class_exists($className,false)){
			return new static($model,$value,$key);
		}
		
		return new $className($model,$value,$key);
		
	}
	
	/**
	 * 获取数据库连接，用于外部调用
	 * @return db
	 */
	public function db(){
		return db::instance($this->db_key);
	}
	
	
	/**
	便于继承时使用
	*/
	protected function init(){
		
		
		if($this->modTable=='' || $this->modTable==null){
			return false;	
		}
		
		$this->db = $this->db();
		if(!in_array($this->db->getTypeName(),$this->supportDbDriver)){
			$this->error('不支持的数据驱动！');
		}
		
		$this->modTable = $this->db->getTableName($this->modTable);
		
		if(!$this->db->existsTable($this->modTable)){				
			$this->error($this->modTable.'模型表不存在!');
			return false;
		}			
		
		if(arrayObj::getItem(self::$hasSavedItem,$this->modTable)!=null){
			$this->modItems = self::$hasSavedItem[$this->modTable];
		}else{
			
			$this->modItems = array_keys( $this->db->getTableFields($this->modTable) );
			self::$hasSavedItem[$this->modTable] = $this->modItems;
			
		}
		
		
	    if(!in_array($this->modKey,$this->modItems)){
			
			$this->error($this->modTable.'唯一标Primary Key:'.$this->modKey.'不存在!');
			return false;
		}
		
		
		$this->reload();
	}
	
	
	public function tableName(){
		return $this->modTable;
	}
	
	
	/**
	 * 重截所有属性的值
	 * @param boolean 是否强制从数据库刷新内容
	 */
	public function reload($refresh=false){
		
		if($this->modVal != ''){
			$key = $this->modKey;
			$this->$key = $this->modVal;
		}
		
		if($refresh == true){
			$this->loaded = $this->finded = false;
		}
		
		$this->get();
	}
	
	
	
	/**
	 * 模型数据转化成数组
	 */
	public function toArray(){
		return $this->modDatas;
	}
	
	/**
	 * 获取模型表的所有字段项
	 */
	public function getModItems(){
		return $this->modItems;
	}
	
	public function labels(){
		
		$flds = $this->db->getTableFields($this->modTable);
		return arrayObj::map($flds,function($k,$v,$list){
			$list[$k] = $v['Comment']!='' ? $v['Comment'] : strtoupper($v['Field']);
			return $list;
		});		
		
	}
	
	/**
	 * 自动设置模型中关键字属性的值
	 */
	protected function setModKeyVal(){

		if($this->{$this->modKey}!=null){
			return $this->{$this->modKey};
		}
		
		$flds = $this->db->getTableFields($this->modTable);
		$info = $flds[$this->modKey];
		if($info['Key'] == 'PRI'){
			return $this->{$this->modKey} = $this->db->lastInsertID();
		}
		
		return $this->{$this->modKey};
		
	}
	
	
	/**
	 * 检测传递的数据项是否有效
	 * @param array $values array(fld=>值)
	 * @param boolean $update 是否为更新模式
	 */
	protected  function _valid($values,$update=false){
		if(!is_array($values) || $values == null){
			$this->error('验证的数据项需数组');
			return false;
		}
		
		$validConfig = $this->validates();
		$labels = $this->labels();
		$checkItems = array_keys($values);
		
		$once       = count($checkItems) == 1;
		if($once){
			if(!isset($validConfig[key($values)]) ){
				$this->error('不存在属性'.key($values));
			}
			$validConfig = array(key($values)=>$validConfig[key($values)]);
		} 
		
		$except = array_diff($checkItems,array_keys($validConfig));
		if($except != null){
			
			$this->error('不存在的属性：'.implode(',',$except));
			return false;
			
		}
		
		
		foreach($validConfig as $k=>$valid){
			
			if($update && !in_array($k,$checkItems)){
				continue;
			}
			
			$v = arrayObj::getItem($values,$k);
			$label = arrayObj::getItem($labels,$k);
			if( $valid['required'] == true && !isset($v) &&  !validate::isNotEmpty($v)){
				$this->error($label.':'.$k.':不能为空！');
				return false;
			}else if($valid['required'] == false && $v == null){
				continue;
			}
			
			if(validate::isCollection($v)){
				$this->error($label.':'.$k.':类型不正确！不能为对象');
				return false;
			}
			
			if($valid['type'] == 'int' && !validate::isInt($v)){
				$this->error($label.':'.$k.':类型不正确，需为int！');
				return false;
			}
			
			if(arrayObj::getItem($valid,'max_length')!=null && strlen($v)>$valid['max_length']){
				$this->error($label.$k.'='.htmlspecialchars($v).'不能超过'.$valid['max_lenth']);
				return false;
			}
					
			if(arrayObj::getItem($valid,'min')!=null && $v<$valid['min'] ){
				$this->error($$label.':'.$k.'='.htmlspecialchars($v).'不能小于'.$valid['min']);
				return false;
			}			
			
			if(arrayObj::getItem($valid,'max')!=null && $v>$valid['max']){
				$this->error($label.':'.$k.'='.htmlspecialchars($v).'不能超过'.$valid['max']);
				return false;
			}
			
		}
		
		return true;
	}
	
	
	/**
	 * 获取模型的验证规则表
	 */
	public function validates(){
		
		if(isset(self::$modValidate[$this->modTable]) ){
			return 	self::$modValidate[$this->modTable];
		} 
		
		$columns = $this->db->getTableFields($this->modTable);
		$valid = array();
		foreach($columns as $k=>$col){
			
			$temp = array();
			if(preg_match('/^([a-z]+)(\((.+)\))?/i',$col['Type'],$m)){
				
				$type     = strtolower($m[1]);
				$unsigned = preg_match('/unsigned/i',$col['Type']);
				 
				if(in_array($type,array('int','tinyint','smallint','mediumint','bigint')) ){
					
					$temp['type'] = 'int';
					if($type == 'tinyint'){
						
						$temp['min'] = $unsigned ? 0 : -128;
						$temp['max'] = $unsigned ? 255 : 127;
						
					}else if($type == 'int'){
					
						$temp['min'] = $unsigned ? 0 : -2147483648;
						$temp['max'] = $unsigned ? 4294967295 : 2147483647;
						
					}else if($type == 'smallint'){
						
						$temp['min'] = $unsigned ? 0 : -32768;
						$temp['max'] = $unsigned ? 65535 : 32767;
						
					}else if($type == 'mediumint'){
						
						$temp['min'] = $unsigned ? 0 : -8388608;
						$temp['max'] = $unsigned ? 16777215 : 8388607;
						
					}else if($type == 'bigint'){
						
						$temp['min'] = $unsigned ? 0 : -9223372036854775808;
						$temp['max'] = $unsigned ? 18446744073709551615 : 9223372036854775807;
						
					}
					
					
				}else if(in_array($type,array('float','double','decimal')) ){
					$temp['type'] = 'float';
				}else if(in_array($type,array('varchar','char','text','tinytext','mediumtext','longtext'))){
					
					$temp['type'] = 'string';
					$temp['max_length'] = arrayObj::getItem($m,3);
					if($temp['max_length']<=0){
						
						$maxLength = array(
							'varchar' =>65532,
							'char'=>65535,
							'tinytext'=>255,
							'text'=>65535,
							'mediumtext'=>16777215,
							'longtext'=>4294967295
						);
						
						$temp['max_length'] = $maxLength[$type];
					}
					
				}else{
					$temp['type'] = 'string';
					if($type == 'timestamp'){
						$temp['max_length'] = 19;
					}else if($type == 'datetime'){
						$temp['max_length'] = 19;
					}else if($type == 'date'){
						$temp['max_length'] = 10;
					}else if($type == 'time'){
						$temmp['max_length'] = 8;
					}
				}
				
			}
			//preg_match
			$temp['required'] =  $col['Default']!='' || $col['Null']=='YES' || $col['Extra'] == 'auto_increment'  ? false : true;
			$valid[$k] = $temp;
		}
		//foreach
		
		self::$modValidate[$this->modTable] = $valid;
		return $valid;
	}
	
	
	/**
	*获取模型中的数据，默认条件为模型modkey=modval的对应的某条数据
	*@param string/array $item 项目名称
	*@return array 信息项数组
	*/
	public function get($item=null){
		
		if($this->modItems == null){
			return array();
		}
		
			
		if($item!=null && !in_array($item,$this->modItems)){
			$this->error($item.',获取的属性不存在');
			return array();	
		}
		
		if($item == null){
			$item = $this->modItems;
		}
			
		
		if(validate::isNotEmpty($this->modVal) == false){
			return null;
		}
		
		if($this->loaded){
			return is_array($item) ?  arrayObj::getExistsItem($item,$this->modDatas) : $this->$item;
		}else{
			
			if($this->finded){
				return is_array($item) ? array() : null;
			}
		}
		

		$result = query::factory()->select($this->modItems)->from($this->modTable)->whereEq(
			array(
				array($this->modKey,$this->modVal)
			)
		)->limit(1)->forUpdate()->execute($this->db);
		$this->finded = true;
		
		if($result == null){
			
			if($item!=null){
				return null;
			}
			
			return array();
		}
		
		$this->loaded = true;
		$infor = $result[0];
		$this->setAttr($infor);
		return is_array($item) ? arrayObj::getExistsItem($infor,$item) : $this->$item;
	}
	
	/**
	 * 设置模型中的关键字的名称
	 * @param string $keyName 键名
	 * @return ORM
	 */
	public function setModKey($keyName){
		
		if($keyName==''){
			return false;
		}
		
	    if(!in_array($keyName,$this->modItems)){
			
			$this->error($this->modTable.'唯一标识符'.$this->modKey.'不存在!');
			return false;
		}
	
		$this->modKey = $keyName;
		if($this->$keyName != null){
			$this->modVal = $this->$keyName;
		}
		
		return $this;
	}
	
	
	/**
	 * 获取关键字的名称
	 */
	public function getModKey(){
		return $this->modKey;
	}
	
	/**
	 * 设置关键字属性的值
	 * @param string $val 值
	 * @return ORM
	 */
	public function setKeyVal($val){
		$this->modVal = $val;
	}
	
	
	/**
	 * 是否存在
	 * @return true/false
	 */
	public function exists(){
		return $this->loaded;
	}
	
	/**
	* 修改信息项
	* @param array $item 需要修改的信息项
	*/
	public function set(array $item){

		if($this->loaded == false){
			return false;
		}
		
		if($this->{$this->modKey} == null){
			return false;
		}
		
		if($this->modItems == null){
			return false;	
		}
		
		
		/**更新时，如果设置了属性，则更新属性*/
		foreach($this->modItems as $k=>$fld){
			
			if(isset($this->$fld) && $this->$fld!=arrayObj::getItem($this->modDatas,$fld) &&  !isset($item[$fld])){
				$item[$fld] = $this->$fld;
			}else if( !isset($item[$fld]) ){
				unset($item[$fld]);
			}
		}
		
		if($this->_valid($item,true) == false){
			return false;
		}		
		
		unset($item[$this->modKey]);
		$res = query::factory()->update($item)->from($this->modTable)->whereEq(array(
				array($this->modKey,$this->modVal)
		))->execute($this->db);
		
		if($res){
			$this->setAttr($item);
		}
		
		return $res;
	}
	
	
	/**
	 * 添加信息
	 * @param array $item
	 */
	public function add(array $item){

		if($this->modItems == null){
			return false;	
		}

		if(!is_array($item)){
			return false;
		}
		
		
		if(!$this->_valid($item)){
			return false;
		}
		
		
		/**如果设置了属性，刚更新属性*/
		foreach($this->modItems as $k=>$fld){
			if(isset($this->$fld) &&  !isset($item[$fld])){
				$item[$fld] = $this->$fld;
			}
		}
		

		$res =  query::factory()->insert($this->modTable,$item)->execute($this->db);
		if($res){
			$this->setAttr($item);
		}
		
		$this->setModKeyVal();
		
		return $res;
	}
	
	
	/**
	*移除对象对应的记录 
	*/
	public function remove(){
		
		if($this->exists() == false){
			return false;
		}
		
		return query::factory()->delete()->from($this->modTable)
		->whereEq(array(
			array($this->modKey,$this->modVal)
		))->execute($this->db);
		
	}
	
	
	/**
	 * 获取模型中变更的项
	 */
	public function getChanged(){
		return $this->changedDatas;		
	}
	
	
	
	public function __set($name,$val){

		$values = array($name=>$val);
		if(!$this->_valid($values,true)){
			return false;
		}
		
		$this->$name = $val;
	}
		
	public function __get($name){
		return $this->get($name);
	}
	
	
	/**
	 * 设置全部或部分属性的值，在更新、添加，获取数据时会被调用
	 * @param array $items
	 */
	protected function setAttr(array $items){
		
		if($items == null){
			return;
		}
		
		$changed = array();
		foreach($items as $fld=>$val){
			
			if(in_array($fld,$this->modItems)){
				$this->{$fld} = $val;
			}
			
			if($this->modDatas!=null && $this->modDatas[$fld]!=$val){
			    $changed[$fld] = array('new'=>$val,'old'=>$this->modDatas[$fld]);
			    $this->onValueChange($fld, $this->modDatas[$fld], $val);
			}	
					
		}
		
		$this->modDatas = array_merge($this->modDatas,$items); 
		$this->changedDatas = $changed;
		
		
	}
	
	/**
	 * 当某项的值改变时，我们需要做什么
	 * @param string $fld 属性名
	 * @param mixed $old 旧值
	 * @param mixed $new 新值
	 */
	protected function onValueChange($fld,$old,$new){
		
	}
	
	
		
	/**
	*异常报告
	*/
	protected function error($exception){	
		website::error('ORM:'.get_class($this).';'.$exception,2);
		
	}
	
}