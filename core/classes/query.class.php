<?php !defined('IN_WEB') && exit('Access Deny!');
/**
数据查询组装类，用户组装各种复杂的查询
@name 查询组装器
@author jackbrown
@version 1.0.0
@time  2015-8-06
@example 
$query->select(array('id','title'=>'cat_name') )->from('guu_prodcat')->whereEq(array(
	array('fld'=>'a','val'=>'b','logic'=>query::COND_AND),
	array('fld'=>'c','val'=>'d','logic'=>query::COND_OR),
	array('fld'=>'e','val'=>'f','logic'=>query::COND_AND),
),query::COND_AND)->limit(1,2);
		
$a = query::factory()->select('a')->from('b');
$b = query::factory()->select('c')->from('d');

$query->union($a,$b)->distinct()->select(array('id','name'))->from('user')->useIndex('user_type')
->leftJoin('info','s')
->using('user_id')
->leftJoin('address','ad')
->on(array(
	array('cd','`ef`',query::COND_AND,'whereEq'),
))		
->rightJoin('score','sc')
->on(array(
	array('ghg','`efx`',query::COND_AND,'whereEq'),
))
->whereGroup(
	array(
		array('id','23',query::COND_AND,'whereEq'),
		array('user_name','16',query::COND_AND,'whereEq'),
		array(
			array('user_email','23',query::COND_OR,'whereEq'),
			array('user_mobile','23',query::COND_OR,'whereEq'),
		),
		array(
			array('user_money',0,query::COND_OR,'whereMaxEq'),
			array('user_score',0,query::COND_OR,'whereMaxEq'),
		)				
	),
	
	query::COND_AND
)->whereEq(array(
	array('fld'=>'a','val'=>':b','logic'=>query::COND_AND),
	array('fld'=>'c','val'=>':d','logic'=>query::COND_AND),
),query::COND_AND)->param(':b',"xxsfd'")->having('a','>=',3);
*/
class query extends model{
	
	Const COND_OR  = ' OR ';
	Const COND_AND = ' AND ';
	Const COND_XOR = ' XOR ';
	Const COND_NOT = ' NOT ';
	
	
	/**保存sql选择信息**/
	protected $sql = array();
	
	
	/**参数**/
	protected  $params = array();
	
	/**保存相关表名*/
	protected $tables = array();
	

	
	/**
	 * 创建query
	 * @param string $query
	 * @return query
	 */
	public static function factory($query=null){
		
		if(validate::isNotEmpty($query) == true){
			$class   = 'query.'.$query;
			$clsName = $query.'Query';
			website::loadClass($class);
			return new $clsName();
		}
		
		return new static();
	}

	public function __construct(){	
		$this->reset();
	}
	
	
	/**
	 * 重置所有条件
	 * @return Query
	 */
	public function reset(){
		
		$this->sql['select'] 
		= $this->sql['table'] 
		= $this->sql['join']
		= $this->sql['order_by'] 
		= $this->sql['having'] 
		= $this->sql['where']
		= $this->sql['limit'] 
		= $this->sql['join_cond']
		= $this->sql['group_by']
		= $this->sql['distinct']
		= $this->sql['use_key']
		= $this->sql['use_index']
		= $this->sql['union']
		= $this->sql['lock_in_share_mode']
		= $this->sql['for_update']
		= $this->sql['cache_type']
		= $this->sql['update_items']
		= $this->sql['insert_items']
		= $this->sql['group_by'] = null;
		$this->sql['is_delete'] = false;
		
		$this->tables = array();
		$table	= property_exists($this,'table') && $this->table!='' ? $this->table : substr(get_class($this),0,-5);
		$table!='' && $this->from($table);	

		return $this;
	}
	
	
	/**
	 * 查找表
	 * @param string/array $table  表名或查询
	 * @return query
	 */
	public function from($table){
		
		if( $table instanceof query){
			
			$this->sql['table'] = $table->toSQL();
			$this->tables = array_merge($this->tables,$table->getTables());
			
		}else if(is_string($table)){
			
			$this->tables[$table] = $table;
			$this->sql['table'] = $this->formatKey($table,true);
			
		}else if(validate::isNotEmpty($table,true)){
			
			$tmp = array();
			foreach($table as $k=>$sub){
				
				if($sub instanceof  query){
					$sub = '('.$sub->toSQL().')';
				}
				
				$tmp[] = !is_numeric($k) ? $this->formatKey($sub,true).' as '.$k : $this->formatKey($sub,true);
				$this->tables[$sub] = $sub;
			}
			
			$this->sql['table'] = implode(',',$tmp);
			
		}
		
		return $this;
	}
	
	
	public function getTables(){
		return $this->tables;
	}
	
	
	public function useIndex($key){
		
		if(is_array($key)){
			$key = implode(',',$key);
		}
		
		$this->sql['use_index'] = $key;
		return $this;
	}
	
	
	/**
	 * 使用索引
	 * @param string $key 索引名
	 */
	public function useKey($key){
		
		if(is_array($key)){
			$key = implode(',',$key);
		}
		
		$this->sql['use_key'] = $key;
		return $this;
	}
	
	
	/**
	 * 查询缓存类别
	 * @param string $type SQL_NO_CACHE 不缓存,SQL_CACHE 缓存,
	 * @return query
	 */
	public function cacheType($type){
		$allow = array(
			'SQL_NO_CACHE',
			'SQL_CACHE',
		);
		
		if(!in_array($type,$allow)){
			$this->error('sql cache type 值不正确');
			return $this;
		}
		
		
		$this->sql['cache_type'] = " $type ";
		return $this;
	}
	
	/**
	 * 组建条件
	 * @param array $flds 条件设置数组由各个条件函数提供 
	 * array( array(fld=>字段名,req请求名或值,logic逻辑,to_url是否加入地址) ),
	 * @param string $relation 条件关系，由各个条件函数提供
	 * @param boolean $quote 值是否需要加引号
	 * @param array $group 是条件组信息
	 */
	protected function buildWhere($flds,$relation,$quote,$group){
		
		$groupStr ='';
		foreach($flds as $k=>$conf){
			
			$key   = arrayObj::getItem($conf,'fld',arrayObj::getItem($conf,0));
			$logic = arrayObj::getItem($conf,'logic',arrayObj::getItem($conf,2,self::COND_AND));
			$val   = arrayObj::getItem($conf,'val',arrayObj::getItem($conf,1));
			
			/**条件值转化***/
			if($val instanceof  query){
				
				$this->tables = array_merge($this->tables,$val->getTables());
				$val   = '('.$val->toSQL().')';
				
			}else if(is_array($val)){
				
			    $val = array_map(function($v){ $v = StrObj::escape_string($v);return "'$v'";},$val);
				$val = '('.implode(',',$val).')';
				
			}else if(preg_match('/`[a-z0-9_\.]+`/i',$val)){
				$val = trim($val,'`');
			}else{
				
			    $val   = StrObj::escape_string($val);
				if($quote == true){
					$val = "'$val'";
				}
				
			}
			
			if($val!=''){
				
				$oldVal = $val;

				if($group==null){
					
					$this->setCondition($key,$val,$relation,$logic);
					
				}else{
					
					if($groupStr==''){
						$logic ='';
					
					}
					$groupStr.= " $logic $key $relation $val ";
					
				}
				
				
			}
		}//eof
		
		if($group!=null && $groupStr!=null){
			$this->setCondition('','('.$groupStr.')','',$group);
		}		
	}
	
	/**
	 * 设置sql条件,通用
	 * @param string $key  字段名
	 * @param string $val 值
	 * @param string $relation 关系>=,>等
	 * @param string $logic 逻辑关系 and or xor
	 */
	public function setCondition($key,$val,$relation,$logic){
		
		if($this->sql['where']!=null && !in_array(trim(end($this->sql['where'])),array('(') ) ){
			$temp =" $logic ".$this->formatKey($key)." $relation $val ";
		}else{
			$temp =" ".$this->formatKey($key)." $relation $val ";
		}
		
		$this->sql['where'][] =  $temp;
		
		
	}	
	
	/**
	 * 条件=
	 * @param array $flds 条件配置
	 *array(fld=>字段名,值,logic逻辑),
	 * @param string $group 组条件and or xor
	 * @return query
	 */
	public function whereEq($flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
		$this->buildWhere($flds,'=',true,$group);
		return $this;
	}

	/**
	 * 条件>
	 * @param array $flds 条件配置
	 * @param string $group 组条件and or xor
	 * @return query
	 */
	public function whereMax($flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
		$this->buildWhere($flds,'>',true,$group);
		return $this;
	}

	/**
	 * 条件>=
	 * @param array $flds 条件配置
	 * @param string $group 组条件and or xor
	 * @return query
	 */
	public function whereMaxEq($flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
		$this->buildWhere($flds,'>=',true,$group);
		return $this;
	}
	
	/**
	 * 条件<
	 * @param array $flds 条件配置
	 * @param string $group 组条件and or xor
	 * @return query
	 */
	public function whereMin($flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
		$this->buildWhere($flds,'<',true,$group);
		return $this;
	}
	
	/**
	 * 条件<=
	 * @param array $flds 条件配置
	 * @param string $group 组条件and or xor
	 * @return query
	 */
	public function whereMinEq($flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
		$this->buildWhere($flds,'<=',true,$group);
		return $this;
	}
	
	/**
	 * 条件!=
	 * @param array $flds 条件配置
	 * @param string $group 组条件and or xor
	 * @return query
	 */
	public function whereNotEq($flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
		$this->buildWhere($flds,'!=',true,$group);
		return $this;
	}
	
	/**
	 * 条件like
	 * @param array $flds 条件配置
	 * @param string $group 组条件and or xor
	 * @return query
	 */
	public function whereLike($flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
		$this->buildWhere($flds,'like',true,$group);
		return $this;
	}
	
	
	/**
	 * 子条件exists
	 * @param array $flds 条件配置array(val,logic),没有fld
	 * @param string $group 组条件
	 * @return query
	 */
	public function whereExists(array $flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
	    foreach($flds as $k=>$v){
	    	
			if( ($v['val'] instanceof query) == false){
				$this->error( 'WhereExists 的值需为子查询');
			}
			
			$v['fld'] = '';
			$flds[$k] = $v;
		}
		
		
		$this->buildWhere($flds,' Exists ',false,$group);
		return $this;
	}
	
	/**
	 * 子条件not exists
	 * @param array $flds 条件配置array(val,logic),没有fld,val必段为子查询query
	 * @param string $group 组条件
	 * @return query
	 */
	public function whereNotExists(array $flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
	    foreach($flds as $k=>$v){
	    	
			if( ($v['val'] instanceof query) == false){
				$this->error( 'WhereExists 的值需为子查询');
			}
			
			$v['fld'] = '';
			$flds[$k] = $v;
		}
		
		
		$this->buildWhere($flds,' NOT Exists ',false,$group);
		return $this;
	}	
	
	
	/**
	 * 
	 * 条件 IN 
	 * @param array $flds fld,val:可以是数组或子查询
	 * @param string $group and/or
	 */
	public function whereIn(array $flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		

		$this->buildWhere($flds,' IN ',false,$group);		
		
		return $this;
	}
	
	
	/**
	 * 条件find_in_set
	 * @param array $flds
	 * @param int $group 条件组的逻辑关系
	 * @return query
	 */
	public function whereFindInSet(array $flds,$group=null){
		
		if(!validate::isNotEmpty($flds,true)){
			return $this;
		}
		
		$flds = array_map(function($v){
			
			$v['fld'] = "FIND_IN_SET('".$v['val']."',".$v['fld'].')';
			$v['val'] = '';
			return $v;
			
		},$flds);
		
		$this->buildWhere($flds,'',false,$group);		
		return $this;		
	}
	
	
	/**
	 * 条件组合
	 * @param array $conditions 条件组
	 * array(
	 * 	array(fld,val,logic,relation),
	 *  array(array(fld,val,logic,relation),array(fld,val,logic,relation))
	 * )
	 * @param string $logic 组合逻辑
	 * @return query
	 */
	public function whereGroup($conditions,$logic){
		
		if(validate::isNotEmpty($conditions,true) == false){
			return $this;
		}
		
		$this->setCondition('', '', '', $logic);
		$this->sql['where'][] =  '(';
		
		$group = array();
		foreach($conditions as $k=>$cond){
			
			if(validate::isNotEmpty($cond,true) == false){
				continue;
			}
			
			foreach($cond as $j=>$item){
				if(!is_array($item)){
					
					if(!method_exists($this,arrayObj::getItem($cond,3))){
						break;
					}
					
					call_user_func(array($this,$cond[3]),array(
						array('fld'=>$cond[0],'val'=>$cond[1],'logic'=>$cond[2])
					));
					
					break;
				}else{
				//eof item not array
					$this->whereGroup($cond,$logic);
					break;
				}
			}
		}
		
		$this->sql['where'][] = ')';
		
		return $this;
		
	}
	
	/**
	 * 只获取条件表达式
	 * @param array $condtions
	 * @param  $logic
	 */
	public function getWhereExp(array $condtions,$logic){
		$q = new self();
		$q->whereGroup($condtions,$logic);
		return trim(trim(implode(' ',$q->sql['where'])),'()');
	}
	
	
	/**
	 * 排序
	 * @param mixed $order //id desc,array('id desc','e desc'),array('id'=>'asc','time'=>'desc')
	 * @return query
	 */
	public function orderby($order){
		
		if(is_string($order)){
			$this->sql['order_by'] = $order;
		}else if(validate::isNotEmpty($order,true)){
			
			$tmp = array();
			foreach($order as $k=>$d){
				$tmp[] = is_numeric($k) ? $d : $k.' '.arrayObj::getRightVal($d,array('desc','asc'),'asc');
			}
			
			$this->sql['order_by'] = implode(',',$tmp);
		}
		
		return $this;
	}
	
	
	/**
	 * 分组
	 * @param string $exp 字段或别名
	 * @param string $sort DESC,ASC
	 * @return query
	 */
	public function groupby($exp,$sort=''){
		$this->sql['group_by'] = $sort!='' ? $exp.' '.$sort : $exp;
		return $this;
	}
	
	
	/**
	 * sql limit
	 * @param int $num 多少数量
	 * @param int $offset 偏移
	 * @return query
	 */
	public function limit($num,$offset=0){
		
		$offset = intval($offset);
		$num    = intval($num);
		
		$offset = $offset<0 ? 0 : $offset;
		$num    = $num<=0 ? 1 : $num;
		
		$this->sql['limit'] = isset($this->sql['update_items']) || arrayObj::getItem($this->sql,'is_delete') == true
							   ? "$num" : "$offset,$num";
		
		return $this;
	}
	
	
	/**
	 * 分页
	 * @param int $page 当前页
	 * @param int $size 大小
	 * @return query
	 */
	public function page($page,$size){
		
		$page = max(intval($page),1);
		$size = max(intval($size),1);
		$limit = $size;
		$offset = ($page-1)*$size;
		return $this->limit($limit,$offset);
	}
	
	
	/**
	 * having 子句
	 * @param string $fld 字段或别名
	 * @param string $exp 表达式 > < >= in
	 * @param mixed $val 值
	 */
	public function having($fld,$exp,$val){
		
		if(is_array($val)){
			
			$val = array_map(function($v){ return "'$v'";},$val);
			$val = '('.implode(',',$val).')';
		}
		
		$this->sql['having'] = "$fld $exp $val";
		
		return $this;
	}
	
	/**
	 * 过滤重复值语句
	 */
	public function distinct(){
		$this->sql['distinct'] = true;
		return $this;
	}
	
	/**
	 * 选择字段
	 * @param mixed $fields
	 * @return query
	 */
	public function select($fields){
		
		if(isset($this->sql['fields'])){
			return $this;
		}
		
		if(is_string($fields)){
			$this->sql['select'] = $this->formatKey( $fields );
		}else if(validate::isNotEmpty($fields,true)){
			
			$tmp = array();
			foreach($fields as $k=>$sub){
				
				if($sub instanceof  query){
					$sub = '('.$sub->toSQL().')';
				}
				
				$tmp[] = !is_numeric($k) ? $this->formatKey($sub).' as '.$k : $this->formatKey($sub);
			}
			
			$this->sql['select'] = implode(',',$tmp);
		}
		
		return $this;
	}
	
	/**
	 * 左连接
	 * @param string/query $table 表名或子查询
	 * @param string $alias　别名,第一参数不为数据时，必填　
	 * @return query
	 */
	public function leftJoin($table,$alias=null){
		
		if($table instanceof query){
			$this->sql['join'][] = array('table'=>'('.$table->toSQL().') as '.$alias,'type'=>'left');
		}else if(is_string($table)){
			$this->sql['join'][] =  array('table'=>$table. ' as '.StrObj::def($alias,$table),'type'=>'left');
		}else if(validate::isNotEmpty($table,true) == true){
			$this->sql['join'][] = array('table'=>'('.implode(',',$table).')','type'=>'left');
		}
		
		return $this;
	}
	
	
	/**
	 * 右连接
	 * @param string/query $table 表名或子查询
	 * @param string $alias　别名　
	 * @return query
	 */	
	public function rightJoin($table,$alias=null){
		
		if($table instanceof query){
			$this->sql['join'][] = array('table'=>'('.$table->toSQL().') as '.$alias,'type'=>'right');
		}else if(is_string($table)){
			$this->sql['join'][] =  array('table'=>$table. ' as '.StrObj::def($alias,$table),'type'=>'right');
		}else if(validate::isNotEmpty($table,true) == true){
			$this->sql['join'][] = array('table'=>'('.implode(',',$table).')','type'=>'right');
		}		
		return $this;
		
	}
	
	
	
	/**
	 * 内连接
	 * @param string/query $table 表名或子查询
	 * @param string $alias　别名　
	 * @return query
	 */	
	public function innerJoin($table,$alias=null){
		
		if($table instanceof query){
			$this->sql['join'][] = array('table'=>'('.$table->toSQL().') as '.$alias,'type'=>'inner');
		}else if(is_string($table)){
			$this->sql['join'][] =  array('table'=>$table. ' as '.StrObj::def($alias,$table),'type'=>'inner');
		}else if(validate::isNotEmpty($table,true) == true){
			$this->sql['join'][] = array('table'=>'('.implode(',',$table).')','type'=>'inner');
		}		
		return $this;		
	}
	
	
	/**
	 * 外连接
	 * @param string/query $table 表名或子查询
	 * @param string $alias　别名　
	 * @return query
	 */	
	public function outerJoin($table,$alias=null){
		
		if($table instanceof query){
			$this->sql['join'][] = array('table'=>'('.$table->toSQL().') as '.$alias,'type'=>'right');
		}else if(is_string($table)){
			$this->sql['join'][] =  array('table'=>$table. ' as '.StrObj::def($alias,$table),'type'=>'right');
		}else if(validate::isNotEmpty($table,true) == true){
			$this->sql['join'][] = array('table'=>'('.implode(',',$table).')','type'=>'outer');
		}	
			
		return $this;		
	}
	
	
	public function using($field){
		
		if(is_array($field)){
			$field = implode(',',$field);
		}
		
		$this->sql['join_cond'][] = array('exp'=>$field,'type'=>'using');
		return $this;
	}
	
	
	/**
	 * on 条件
	 * @param array $condtions
	 * @param string $defLogic
	 * @return query
	 */
	public function on(array $condtions,$defLogic=query::COND_AND){
		$this->sql['join_cond'][] = array('exp'=>$this->getWhereExp($condtions,$defLogic),'type'=>'on');
		return $this;
	}
	
	
	/**
	 * union查询
	 * @param query $query1
	 * @param query $query2
	 */
	public function union(){
		
		$subquery = func_get_args();
		if($subquery == null){
			return $this;
		}
		
		$query = array();
		foreach($subquery as $q){
			
			if($q instanceof query){
				$query[] = '('.$q->toSQL().')';
			}
		}
		
		if($query !=null){
			$this->sql['union'] = implode(' UNION ',$query);
		}
		
		return $this;
	}
	
	
	/**
	 * 共享锁
	 */
	public function lockInShareMode(){
		unset($this->sql['for_update'] );
		$this->sql['lock_in_share_mode'] = ' LOCK IN SHARE MODE';
	}
	
	
	/**
	 * 独占锁
	 */
	public function forUpdate(){
		unset($this->sql['lock_in_share_mode'] );
		$this->sql['for_update'] = ' FOR UPDATE';
		return $this;
	}
	
	
	/**
	 * 更新操作
	 * @param array $items array(字段名=>值，如果用`符号包括，则为一个表达式，不会加引号
	 * @return query
	 */
	public function update(array $items){
		
		if($items == null){
			$this->error('更新数据项不能为空');
		}
		
		$list = array();
		foreach($items as $k=>$v){
			
			if($v instanceof query){
				$list[]  = $v->toSQL();
			}else{
				$v = StrObj::escape_string($v);
				$list[] = $this->formatKey($k).'='.(strstr($v,'`') ?  trim($v,'`') : "'$v'");
			}
			
		}
		
		$this->sql['update_items'] = implode(',',$list).' ';
		return $this;
		
	}
	
	
	/**
	 * 设置为删除操作
	 * @return query
	 */
	public function delete(){
	    $this->sql['is_delete'] = true;
	    return $this;
	}
	
	
	/**
	 * 插入操作
	 * @param string $table 表名
	 * @param array $datas 数据array(字段=>值),
	 * @return query
	 */
	public function insert($table,array $datas){
	    
	    if($datas == null){
	        $this->error('插入的数据项不能为空');
	    }
	    
	    if($table == ''){
	        $this->error('插入的表名不能为空');
	    }
	    
	    $values = array();
	    $fields = array();
	    foreach($datas as $k=>$v){
            $v = @mysql_escape_string($v);
            $values[] = "'$v'";
            $fields[] = $this->formatKey($k);
	    }
	    
	    $this->tables[$table] = $table;
	    $this->sql['table'] = $this->formatKey($table,true);
	    $this->sql['insert_items'] = '('.implode(',',$fields).') values('.implode(',',$values).')';
	    
	    return $this;
	}
	
	
	/**
	 * 执行sql;
	 * @param db/string $driver 数据连接对象,不填刚采用默认的连接
	 * @param callback $rowCallback 对每行执行回调
	 * @return array
	 */
	public function execute($driver=null,$rowCallback=null){
		
		$sql = $this->toSQL();
		if($driver == null){
			$db = db::instance();
		}else{
			$db = $driver;
		}
		
		if(!($db instanceof  db)){
			$this->error('错误的数据连接对象');
		}
		
		$qtype = $db->getQueryType($sql);
		if($qtype == $db::SELECT){
			return $db->getResArray($sql,false,$rowCallback);
		}else{
			$db->setCurentTable(arrayObj::getItem(array_values($this->tables),0,''));
			return $db->query($sql);
		}
		
		
	}
	
	/**
	 * 组装生成sql
	 * @return string
	 */
	public function toSQL(){

		$distinct   = $this->sql['distinct'] === true ? ' DISTINCT ' : '';
		$cache      = $this->sql['cache_type'];
		$select     = $this->sql['select']!='' ? 'SELECT '.$cache.$distinct.$this->sql['select'] : 'SELECT * ';
		$table      = $this->sql['table']!='' ?  ' FROM  '.$this->sql['table'] : '';
		$useindex   = $this->sql['use_index']!='' ? ' USE INDEX('.$this->sql['use_index'].')' : '';
		$usekey     = $this->sql['use_key']!='' ? ' USE KEY('.$this->sql['use_key'].')' : '';
		$where      = $this->sql['where']!=null ? ' WHERE '.implode(' ',$this->sql['where']) : '';
		$orderby    = $this->sql['order_by'] !='' ?  ' ORDER BY '.$this->sql['order_by'] : '';
		$limit      = $this->sql['limit']!='' ?  ' LIMIT '.$this->sql['limit'] : '';
		$having     = $this->sql['having']!='' ? ' HAVING '.$this->sql['having'] : '';
		$groupby    = $this->sql['group_by']!='' ? ' GROUP BY '.$this->sql['group_by'] : '';
		$union      = $this->sql['union'];
		$lock       = arrayObj::getItem($this->sql,'lock_in_share_mode')!='' ? $this->sql['lock_in_share_mode'] : '';
		$lock       = arrayObj::getItem($this->sql,'for_update')!='' ? $this->sql['for_update'] : $lock;
		$isDel      = $this->sql['is_delete'];
		$updateItems = $this->sql['update_items'];
		$insertItems = $this->sql['insert_items'];
		
	   if($insertItems!=''){
	       return $this->getParam(trim('INSERT INTO '.$this->sql['table'].$insertItems));
	   }else if($updateItems != ''){
			return $this->getParam(trim('UPDATE '.$this->sql['table'].' SET '.$updateItems.$where.$limit));
		}else if($isDel){
		    return $this->getParam(trim('DELETE '.$table.$where.$limit));
		}else{
			
			/**有union的时候很多选效无效，将只用union**/
			if($union !=''){
				$this->sql['join'] = $this->sql['join_cond'] = $having = $groupby = 
				$distinct = $select = $table = $useindex = $usekey = $where = null;
			}
			
			$join       = ' ';
			if($this->sql['join']!=null && $this->sql['join_cond'] ){
				
				foreach($this->sql['join'] as $k=>$j){
					
					$whereExp = '';
					if(isset($this->sql['join_cond'][$k])){
						
						$exp = $this->sql['join_cond'][$k];
						
						if($exp['type'] == 'on'){
							$whereExp = ' ON '.$exp['exp'];
						}else if($exp['type'] == 'using'){
							$whereExp = ' using('.$exp['exp'].')';
						}
						
					}
					
					$join.= strtoupper($j['type']).' JOIN '.$j['table'].$whereExp.' ';
				}
				
			}
			
			
			return $this->getParam(trim($union.$select.$table.$useindex.$usekey.$join.$where.$groupby.$orderby.$having.$limit.$lock));
		}
		
		
	}
	
	
	protected  function getParam($sql){
		
		if($this->sql == '' || $this->params == null){
			return $sql;
		}
		
		foreach($this->params as $p=>$v){
			$sql = str_replace($p,@mysql_escape_string($v),$sql );
		}
		
		return $sql;
	}
	
	
	/**
	 * 增加参数绑定
	 * @param string $p
	 * @param mixed $v
	 * @return query
	 */
	public function param($p,$v){
		$this->params[$p] = $v;
		return $this;
	}
	
	
	
	public function  __toString(){
		return $this->toSQL();
	}

	
	/**
	 * 格式化字段表名或别加上··符号
	 * @param string $key
	 * @return string
	 */
	protected function formatKey($key,$table=false){
		
		if($key == '' || $key == '*'){
			return $key;
		}
		
		if(!preg_match('/^[a-z_0-9]+$/i',trim($key))){
			return $key;
		}
		
		if(strstr($key,'`')){
			$key = trim($key,'`');
			return $table ? "`__PRE__$key`" : "`$key`";;
		}
		
		if(strstr($key,'.')){
			
			$temp = explode('.',$key);
			$key  = $temp[0].'`'.$temp[1].'`';
		}else{
			$key = $table ? "`__PRE__$key`" : "`$key`";
		}
		
		return $key;
	}
	
	protected function error($msg){
		website::error($msg,2,2);
	}
	
} 