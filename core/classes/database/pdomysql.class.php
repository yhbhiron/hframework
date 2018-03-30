<?php
if(!defined('IN_WEB')){
	exit('Aceess Deny!');
}

/**
 * pod 数据库连接器
 * @author yhb
 *
 */
class dataBasePdoMysql extends dataBaseMysql{
	
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
		
		try{
			$this->con = new PDO('mysql:dbname='.$this->config['database'].';host='.$this->config['host'],$this->config['user'],$this->config['passwd']);
			website::debugAdd('PDO连接到数据库'.$config['user'].'@'.$config['host'].'#'.$config['database'].'... ok',$time);
		}catch(Exception $e){
			self::error($e->getMessage(),2,$e->getCode());
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
		$this->con->exec('SET NAMES '.$this->config['charset']);
		
		$GLOBALS['sql_time'] = !isset($GLOBALS['sql_time']) ? 0 : $GLOBALS['sql_time'];
		$expStr = ';';
		
		$std    = $this->con->prepare($sql);
		$result = $std->execute();
		if($result){
			
			if(website::$config['debug'] || $this->config['log-queries-not-using-indexes'] == true){
				
				$GLOBALS['sql_time'] += (website::curRunTime()-$time)*1000;
				if( $qType != 'explain' &&  $qType == db::SELECT ){
					
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
			
			$this->std = $std;
			$this->doQueryEvent($qType,$sql);
			return $std;
			
		}else{
			
			$this->std  = null;
			self::error($sql.';'.implode(',',$std->errorInfo()),2,$std->errorCode());
			return false;
			
		}
				
	}	
	
	
	public function isConnected(){
		
		$timeout = false;
		if(is_object($this->con)){
			
			$status = @$this->con->getAttribute(PDO::ATTR_SERVER_INFO);
			$error = $this->con->errorInfo();
	        if(isset($error[1]) && $error[1] == 2006){
	          $timeout = true;
	        }
	        
		}else{
		 	$timeout = true;
		}
        
		return  !$timeout;
	}
	
	
	public function close(){
		return false;
	}
	

	public function recordRows($sql){
		
		$result=$this->query($sql);
		return $result->rowCount();
		
	}
	
	
	public function affectedRows(){
		
		if(!$this->std){
			return 0;
		}
		
		return $this->std->rowCount();
	}
	

	
	function getResArray($sql,$single=false,$rowCallback=null){

		$result=$this->query($sql);
		if(!$result){
			return array();	
		}
		
		/**返回单条**/
		if($single){
			
			$data = is_callable($rowCallback) ? call_user_func($rowCallback,$result->fetch(PDO::FETCH_ASSOC)) : $result->fetch(PDO::FETCH_ASSOC);
			$result->closeCursor();
			return $data;
			
		}
		
		
		/**返回多条**/
		$results = array();
		while($res = $result->fetch(PDO::FETCH_ASSOC)){
			
			if(is_callable($rowCallback)){
				$results = call_user_func($rowCallback,$results,$res);
			}else{
				array_push($results,$res);
			}
			
		}
		
		$result->closeCursor();
		return $results;
		
	}	
	
	

	
	
	public function start(){
	    
	    if(self::$transActionStarted == true){
	        $this->error('事务不允许嵌套或事务开始后不提交', 2);
	    }
	    
	    self::$transActionStarted = true;
		self::$forceMain[$this->config['db_key']] = true;
		return $this->con->beginTransaction();
		
	}	
	
	
	public function commit(){
		
	    
		$res = $this->con->commit();
		self::$transActionStarted = false;
		self::$forceMain[$this->config['db_key']] = false;
		return $res;	
	}	
	
	
	public function rollBack(){
		
	    self::$transActionStarted = false;
		self::$forceMain[$this->config['db_key']] = false;
		return $this->query('rollback');
	}

	
	public function useDatabase($dbName=''){
	
		
		if($dbName==''){
			$this->currentDb = $this->config['database'];
		}else{
			$this->currentDb = $dbName;
		}
		
		return $this->query('USE '.$this->currentDb);
	
	}	
	

	


	
}
?>