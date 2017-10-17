<?php
if(!defined('IN_WEB')){
	exit('Access Deny!');
}
/**
mysql数据库操作类
@author jackbrown
@version 3.0.9
@time  2011-8-10
@example 
*/

class dataBaseMysql extends db{
	
	/**当前数据库*/
	protected $currentDb;
	
	protected $dbTypeName = 'mysql';
	
	
	protected function connect(){
		
		if($this->isConnected()){
			return $this->con;
		}
		
		$config = $this->config;
		$time     = website::curRunTime();
		
		/**连接数据库**/
		if(arrayObj::getItem($config,'pconnect') === false){
			$this->con = @mysql_connect($config['host'],$config['user'],$config['passwd']);
		}else{
			$this->con = @mysql_pconnect($config['host'],$config['user'],$config['passwd']);
		}
		

		if( $this->con ){
			
			$dbRes = $this->useDatabase();
			if(!$dbRes){
				self::error("你选择的数据库".$config['database']."不存在!",2,mysql_errno($this->con ));
			}
			
			
			website::debugAdd('连接到数据库'.$config['user'].'@'.$config['host'].'#'.$config['database'].'... ok',$time);
				
		}else{
			self::error(mysql_error()."无法连接服务器".$config['host']."，请检查你的连接的服务器是否存在，或你的登录用户名和密码是否正确!",2,mysql_errno());
		}	

		self::$connected[$this->config['db_key']][$this->config['db_host']] = $this;
				
	}
	
	
	
	public function query($sql){
		
		if(empty($sql) || $sql==''){
			return false;
		}
		
		!$this->isConnected() && $this->connect();
		
		$sql = $this->compile($sql);
		$qType =  $this->getQueryType($sql);
		
		if(arrayObj::getItem(self::$forceMain,$this->config['db_key']) === false &&  $this->config['db_host']!='slaves' 
			&&  $qType == db::SELECT && $this->config){
			return self::instance($this->config['db_key'],'slaves')->query($sql);
		}
		
		$time = website::curRunTime(true);
		mysql_query('SET NAMES '.$this->config['charset']);
		
		$GLOBALS['sql_time'] = !isset($GLOBALS['sql_time']) ? 0 : $GLOBALS['sql_time'];
		$expStr = ';';
		$this->lastQueryCmd = $sql;
		
		if($result = @mysql_query($sql,$this->con)){
			
			if(website::$config['debug'] || $this->config['log-queries-not-using-indexes'] == true){
				
				$GLOBALS['sql_time'] += (website::curRunTime()-$time)*1000;
				if($qType == db::SELECT ){
					
					$explain = 'explain '.$sql;
					$explainRes  = $this->getResArray($explain);
					if($explainRes !=null ){
						
						$expStr='<br />';
						foreach($explainRes as $j=>$res){
							
							foreach($res as $k=>$info){
								$expStr.=" <font color=#888888 >$k</font>: $info; ";
							}
							
							$expStr.='<br />';
						}
						
					}
					
					$expStr='<font color=red>'.$expStr.'</font>';
				}
				
				
			}
			
			if( website::$config['debug']==true && $qType!=db::EXPLAIN){
				website::debugAdd(htmlspecialchars($sql).$expStr,$time,false,true);
			}
			
			/**慢查询日志**/
			if(
				$qType!=db::EXPLAIN && 
				$this->config['long_query_time']>0 && 
				$this->config['long_query_time']<=website::curRunTime(true)-$time
				 && is_writable($this->config['log-slow-queries'])
			){
				$this->slowlog($sql.'[Time:'.(website::curRunTime(true)-$time).'s]');
				
			}
			
			$this->doQueryEvent($qType,$sql);
			return $result;
			
		}else{
			
			self::error($sql.';'.mysql_error($this->con),2,mysql_errno($this->con));
			return false;
			
		}
				
	}
	
	
	protected  function doQueryEvent($qtype,$sql){
	    
	    $params = array($this->table,$this->currentDb,$sql);
	    if($qtype == self::INSERT){
	       website::doEvent('database.mysql.insert',$params);
	    }else if($qtype == self::DELETE){
	        website::doEvent('database.mysql.delete',$params);
	    }else if($qtype == self::UPDATE){
	        website::doEvent('database.mysql.update',$params);
	    }
	    
	    
	}
	
	
	public function isConnected(){
		return is_resource($this->con) && mysql_ping($this->con);
	}
	
	
	public function close(){
		
		if(arrayObj::getItem($this->config,'pconnect') == false){
			return @mysql_close($this->con);
		}
		
		return false;
	}
	

	public function recordRows($sql){
		
		$result=$this->query($sql);
		return mysql_num_rows($result);
		
	}
	
	
	public function recordSum($sql){
		
		$res = $this->getResArray($sql,true);
		return $res['resum'];
	}

	
	function getResArray($sql,$single=false,$rowCallback=null){

		$result=$this->query($sql);
		if(!$result){
			return array();	
		}
		
		/**返回单条**/
		if($single){
			
			$data = is_callable($rowCallback) ? call_user_func($rowCallback,mysql_fetch_assoc($result)) : mysql_fetch_assoc($result);
			mysql_free_result($result);
			return $data;
			
		}
		
		
		/**返回多条**/
		$results = array();
		while($res = @mysql_fetch_assoc($result)){
			
			if(is_callable($rowCallback)){
				$results = call_user_func($rowCallback,$results,$res);
			}else{
				array_push($results,$res);
			}
			
		}
		
		mysql_free_result($result);
		return $results;
		
	}	
		

	
	public function getTableInfo($table){
		return $this->getResArray("show table status from {$this->config['database']} like '$table'",true);
	}
	
	/**
	*影响行数
	*/
	public function affectedRows(){
		return mysql_affected_rows($this->con);	
	}	
	
	
	public function start(){
	    
	    if(self::$transActionStarted == true){
	       $this->error('事务不允许嵌套或事务开始后不提交', 2);
	    }
		
	    self::$transActionStarted = true;
		self::$forceMain[$this->config['db_key']] = true;
		return $this->query('set autocommit = 0') && $this->query('start transaction');	
		
	}	
	
	
	public function commit(){
	    
		
	    self::$transActionStarted = false;
		self::$forceMain[$this->config['db_key']] = false;
		return $this->query('commit') && $this->query('set autocommit = 1');	
	}	
	
	
	public function rollBack(){
		
		self::$forceMain[$this->config['db_key']] = false;
		self::$transActionStarted = false;
		return $this->query('rollback');
	}

	
	public function lastInsertID(){
	
		self::$forceMain[$this->config['db_key']] = true;
		$res = $this->getResArray('select last_insert_id() as id',true);
		self::$forceMain[$this->config['db_key']] = false;
		
		return $res['id'];
		
	}
	
	
	/**
	*优化表
	*/
	public function opTable($table){
		
		$status = $this->query('optimize table '.$table);
		return $status;
	}

	
	/**
	*导出数据
	*@param string $table 要导出的表
	*@return string 可执行的sql语句
	*/
	public function dumpData($table='',$file=''){
		
		if($table != ''){
			
			return $this->getTableSql($table);
			
		}else{
			
			$tables	= $this->getTables();
			
			if($tables!=null){
				
				$outSql = '';
				foreach($tables as $key=>$table){
					
					$outSql.= $this->getTableSql($table);
					
				}
				
				return $outSql;
			}
			
		}	
		
	}
	
	
	public function useDatabase($dbName=''){
	
		
		if($dbName==''){
			$this->currentDb = $this->config['database'];
		}else{
			$this->currentDb = $dbName;
		}
		
		return mysql_select_db($this->currentDb,$this->con);
	
	}	
	
	/**
	*复制一个查询的数据到另一个表,此表可以是一个临时表
	*@param string $table 目标数据表的名称
	*@param string $sql 可执行的sql语句
	*@param boolean $tmp 是否创建一个临时表
	*/
	public function copyFromSQL($table,$sql,$tmp=false,$memory=false){
		
		$tmpStr = '';
		if($tmp == true){
			$tmpStr = 'TEMPORARY';
			$type = "type=heap";
		}
		
		if($memory){
			$type = "ENGINE=MEMORY";	
		}
		
		if($this->existsTable($table)){
			
			return $this->query("insert into  $table $sql");
			
		}else{
			return $this->query("create $tmpStr table $table $type $sql");
		}
	}	
	


	public function getTableSql($table,$onlyStd=false){
		
		$structStr = $this->getResArray("show create table $table",true);
		$structStr = $structStr['Create Table'];
		
		if($onlyStd){
			return $structStr;	
		}
		
		
		$fields = $this->getTableFields($table);
		$fldStr = '';
		foreach($fields as $key=>$fld){
				
			$fldStr .= "`{$fld['Field']}`,";
		}
		
		$fldStr   = substr($fldStr,0,-1);	
		
		
		$dataStr = $this->getResArray("select*from $table",false,function($result,$row)use($fldStr,$table){
			
				if(is_array($result)){
					$result = '';
				}
			
				
				$eachData = '';
				foreach($row as $key=>$value){
					$eachData .= is_null($value) ? 'NULL,' : "'".@mysql_real_escape_string($value)."',";
				}
				
				$eachData = substr($eachData,0,-1);
				$result.="insert into `$table`($fldStr) values($eachData);\n";	

				return $result;
			}
		);
		
		if(is_array($dataStr)){
			$dataStr = '';
		}
		
		return $structStr.";\n".$dataStr;
			
	}

	public function getTables($force=false,$database=null){
		
		if($database == null){
			$database = $this->config['database'];
		}		
			
		if(isset(self::$tables[$database]) && !$force){
			return self::$tables[$database];
		}

		$tables = $this->getResArray('show tables',false,function($results,$row){
				$results[] = current($row);
				return $results;
			}
		);
		
		self::$tables[$database] = $tables;
		return $tables;
	}


	
	public function getTableFields($table){
		
		if(isset(self::$columns[$this->config['database'].'_'.$table])){
			return self::$columns[$this->config['database'].'_'.$table];
		}
				
		$fields = $this->getResArray("SHOW FULL COLUMNS FROM `$table`",false,function($results,$row){
				$results[$row['Field']] = $row;
				return $results;
			}
		);
		
		self::$columns[$this->config['database'].'_'.$table] = $fields;
		return $fields;		
		
	}
	
}