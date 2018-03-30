<?php !defined('IN_WEB') && exit('Access Deny!');

class dataBaseMysqli extends dataBaseMysql{
	
	/**
	 * 保存statement 对象
	 */
	public $std;
	
    
    protected function connect(){
    
        if($this->isConnected()){
            return $this->con;
        }
        
        Website::debugWarning('Connect Database again start');
        $config = $this->config;
        $time     = website::curRunTime();
    
        /**连接数据库**/
        $this->con = new mysqli($config['host'],$config['user'],$config['passwd'],$config['database']);
    
    
        if( $this->con->connect_error ){
            self::error("无法连接服务器".$config['host'].":".$this->con->connect_error,2,$this->con->connect_error_no);
        }else{
            website::debugAdd('连接到数据库'.$config['user'].'@'.$config['host'].'#'.$config['database'].'... ok',$time);
        }
    
        self::$connected[$this->config['db_key']][$this->config['db_host']] = $this;
    
    }
    
    public function isConnected(){
        return ($this->con instanceof mysqli) && @$this->con->ping();
    }    
    
    /**
     * @param $sql
     * @return MySQLi_STMT
     */
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
		$this->con->query('SET NAMES '.$this->config['charset']);
		
		$GLOBALS['sql_time'] = !isset($GLOBALS['sql_time']) ? 0 : $GLOBALS['sql_time'];
		$expStr = ';';
		
		$result    = $this->con->query($sql);
		if($result){
			
			if(website::$config['debug'] || $this->config['log-queries-not-using-indexes'] == true){
				
				$GLOBALS['sql_time'] += (website::curRunTime()-$time)*1000;
				if( $qType  != 'explain' &&  $qType == db::SELECT ){
					
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
				$this->slowlog($sql.'['.(website::curRunTime(true)-$time).'s]');
				
			}
			
			$this->doQueryEvent($qType,$sql);
			return $result;
			
		}else{
			
			self::error($sql.';'.$this->con->error,2,$this->con->errno);
			return false;
			
		}
				
	}

	public function close(){
		return false;
	}
	

	public function recordRows($sql){
		
		$result=$this->query($sql);
		return $result->num_rows;
		
	}
	
	
	public function affectedRows(){
		return $this->con->affected_rows;
	}	
	
	
	function getResArray($sql,$single=false,$rowCallback=null){

		$result =$this->query($sql);
		if(!$result){
			return array();	
		}
		

		/**返回单条**/
		if($single){
			
			$data = is_callable($rowCallback) ? call_user_func($rowCallback,$result->fetch_assoc()) : $result->fetch_assoc();
			$result->free();
			return $data;
			
		}
		
		
		/**返回多条**/
		$results = array();
		while($res = $result->fetch_assoc()){

			if(is_callable($rowCallback)){
				$results = call_user_func($rowCallback,$results,$res);
			}else{
				array_push($results,$res);
			}
			
		}
		
		$result->free();
		return $results;
		
	}		
	
	public function start(){
	    
	    if(self::$transActionStarted == true){
	        $this->error('事务不允许嵌套或事务开始后不提交', 2);
	    }
	    
	    $this->con->autocommit(false);
	    self::$transActionStarted = true;
		self::$forceMain[$this->config['db_key']] = true;
		return  method_exists($this->con,'begin_transaction') ? $this->con->begin_transaction() : $this->con->query('Start Transaction');
		
	}	
	
	
	public function commit(){
		
	    
		$res = $this->con->commit();
		self::$transActionStarted = false;
		self::$forceMain[$this->config['db_key']] = false;
		$this->con->autocommit(true);
		return $res;	
	}	
	
	
	public function rollBack(){
		
	    self::$transActionStarted = false;
		self::$forceMain[$this->config['db_key']] = false;
		return $this->con->rollback();
	}

	
	public function lastInsertID(){
	
		self::$forceMain[$this->config['db_key']] = true;
		$res = $this->getResArray('select last_insert_id() as id',true);
		self::$forceMain[$this->config['db_key']] = false;
		
		return $res['id'];
		
	}
	
	
	
	public function useDatabase($dbName=''){
	
		
		if($dbName==''){
			$this->currentDb = $this->config['database'];
		}else{
			$this->currentDb = $dbName;
		}
		
		return $this->con->select_db($dbName);
	
	}	
	
	
}