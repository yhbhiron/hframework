<?php
if(!defined('IN_WEB')){
	exit('Access Deny!');
}

/**
* @author jackbrown
* @version 1.0.0
* @since  2015-10-23
**/
abstract class DB extends model{
	
	/**当前连接配置*/
	protected $config = array();
	
	/**已经连接的连接信息*/
	protected static $connected = array();
	
	/**当前连接**/
	protected $con;
	
	/**当前使用的表名*/
	protected $table = '';
	
	/**是否 强制使用主库**/
	protected static $forceMain = array();
	
	/**获取的表名**/
	protected static $tables = array();
	
	
	/**表的结构*/
	protected static $columns = array();
	
	/**当前数据列表*/
	protected $dblist = array();
	
	/**数据库所有配置列表*/
	protected static $dbConfig;
	
	/**是否已经开始使用了事务*/
	protected static $transActionStarted =false;
	
	/**最后一条执行的sql语句或命令*/
	protected $lastQueryCmd = '';
	
	
	/**查找查询*/
	const SELECT  = 1;
	
	/**插入操作*/
	const INSERT  = 2;
	
	/**更新操作*/
	const UPDATE  = 3;
	
	/**删除操作*/
	const DELETE = 6;
	
	/**解析操作*/
	const EXPLAIN = 5;
	
	/**其它*/
	const OTHER   = 4;	

	protected $dbTypeName = 'db';
	
	
	protected function __construct(array $config){		
		$this->config = $config;
		$this->connect();
	}
	
	
	/**
	 * 实例化数据库对象
	 * @param string $dbkey 数据库配置组名
	 * @param string $database 选择的据组
	 * @return db
	 */
	public static function instance($dbkey=null,$database=null){
		
		$dbkey    = StrObj::def($dbkey,arrayObj::getItem(website::$config,'def_db'));
		$database = StrObj::def($database,'main');
		
		if(isset(self::$dbConfig)){
			$dbConfig = self::$dbConfig;
		}else{
			self::$dbConfig = $dbConfig   = website::loadConfig('dbcon',false);
		}
		
		if(!isset($dbConfig['databases'])){
			self::error('无法加载数据库配置的数据库列表',2);
			return false;
		}
		
		$curConfig  = arrayObj::getItem($dbConfig['databases'],$dbkey);
		
		if($curConfig == null){
			self::error('无法加载数据库配置文件'.$dbkey,2);
			return false;
		}	

		if(!isset($curConfig['connect'])){
			self::error('无法加载数据库配置'.$dbkey.'的连接列表',2);
			return false;
		}		
		
		$type       = $curConfig['type'];
		$config   = arrayObj::getItem($curConfig['connect'],$database);
		
		/**重库切换*/
		if($database == 'slaves' && $config!=null){
			$config = $config[array_rand($config)];
		}
		
		if($config == null && $database == 'slaves'){
			$database = 'main';
			self::$forceMain[$dbkey] = true;
			$config = arrayObj::getItem($curConfig['connect'],$database);
		}
				
		if($config == null){
			self::error('无法加载数据库配置文件'.$dbkey.'-'.$database,2);
			return false;
		}

		if( isset(self::$connected[$dbkey][$database]) &&  self::$connected[$dbkey][$database] !=null ){

			$c = self::$connected[$dbkey][$database];
			if($c->isConnected() == true){
				return $c;
			}
			
		}		
		
		$config['db_key']     = $dbkey;
		$config['db_host']   = $database;
		$config['log-slow-queries']              = arrayObj::getItem($dbConfig,'log-slow-queries');
		$config['long_query_time'] 				 = arrayObj::getItem($dbConfig,'long_query_time');
		$config['log-queries-not-using-indexes'] = arrayObj::getItem($dbConfig,'log-queries-not-using-indexes');

		$class = 'database'.ucfirst($type);		
		return new $class($config);
		
	}
	
	/**
	 * 连接
	 */
	abstract  protected  function connect();
	
	/**
	 * 获取查询的类别
	 * @param string $sql 查询语句
	 */
	public function getQueryType($sql){
		
		$regexp = '/^\s*([a-z]+)\s*/i';
		if( preg_match($regexp,$sql,$match) ){
			
			$type = strtolower(trim($match[1]));
			switch($type) {
				
				case 'select':
					return db::SELECT;
					break;
				case 'update':
					return db::UPDATE;
					break;
				case 'explain':
					return db::EXPLAIN;
					break;
				case 'insert':
					return db::INSERT;
					break;
				case 'delete':
					return db::DELETE;
					break;
					
			}
		}
		
		return db::OTHER;
	}	
	
	
	/**
	 * 解析sql的一些内部变量
	 * @param string $sql sql语句
	 * @return string;
	 */
	protected function compile($sql){
		
		$sql =  str_replace('__PRE__',$this->config['db_pre'],$sql);
		$sql =  str_replace('__TABLE__',$this->table,$sql);
		$sql =  preg_replace('/('.$this->config['db_pre'].')+/',$this->config['db_pre'],$sql);
		
		return $sql;
	}
	
	
	/**
	*检查表是否存在
	*@param String $table表名
	*@return true/false;
	*/
	public function existsTable($table){
			
		return in_array($table,$this->getTables());
		
	}
	
	
   /**
    * 记录慢查询日志
   */
	protected function slowlog($msg){
	  	
	  	if(website::$config['logs']){
			
			website::loadFunc('filer');
			website::$config['log_file'] =  filer::mkTimeFile('slowlogs.txt',$this->config['log-slow-queries']);
		}	
		  	
		$msg = strip_tags($msg);
		website::log($msg,'mysql_slow');
		  
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
					
					$outSql.= $this->getTableSql($table)."\n\r";
					
				}
				
				return $outSql;
			}
			
		}	
		
	}	
	
	
	/**
	 * 获取表名，主动加前缀
	 * @param string $table 表名
	 * @return string
	 */
	public function getTableName($table){
		
		if($this->config['db_pre'] == ''){
			return $table;
		}
		
		if(substr($table,0,strlen($this->config['db_pre']))!= $this->config['db_pre']){
			return  $this->config['db_pre'].$table;
		}
		
		return $table;
	}
	
	
	public function setCurentTable($table){
	    $this->table = $this->getTableName($table);
	}
	
	
	/**
	 * 获取最后执行的查询语句或命令
	 * @return string
	 */
	public function getLastQueryCmd(){
	    return $this->lastQueryCmd;
	}
	
	
	/**
	 * 是否已经连接
	 */
	abstract protected function isConnected();
	
	
	/**
	 * 获取一个查询结果
	 * @param string $sql 查询语句
	 * @return  resource 查询结果,失败时返回false;
	 */	
	abstract public function query($sql);
	
	
	/**
	 * 如果使用静态的变量保存$con,在子类中会解发此函数
	 * 所以不能指定关闭的连接为self::$con
	 */
	abstract public function close();	
	
	
	/**
	*记录总数
	*$source需为query的查询指针
	*/
	abstract public function recordRows($sql);	
	
	
	/**
	 * 获取影响的行数
	 */
	abstract public function affectedRows();
	
	
	/**
	 * 获取记录的总数
	 *
	 * @param string $sql 里需有一个别名resum
	 * @return int 返回记录总数
	 */
	abstract public function recordSum($sql);

	
	/**
	*获取指定查询语句的数组
	*@param string $sql 查询语句
	*@param  boolean $single 是否只反回一条
	*@param callable $rowCallback 对每条记录使用回调函数
	*@return array 查询结果数组
	*/
	abstract public function getResArray($sql,$single=false,$rowCallback=null);
	


	
	/**
	 * 获取最后插入ID
	 * 比mysql_insert_id安全
	 * @return int
	 */
	abstract public function lastInsertID();
	

	
	/**
	 * 获取表信息
	 * @param string $table 表名
	 * @param string $db 指定数据库，默认为当前连接的数据库
	 * @return array;
	 */
	abstract public function getTableInfo($table);
	
	
	
	/**
	*开始一个事务,用于事务处理
	*/
	abstract public function start();
	
	
	/**
	*提交事务中的数据
	*/
	abstract public function commit();


	/**
	*回滚事务
	*/
	abstract public function rollBack();

	
	/**
	*转换数据库
	*@param string $dbName 数据库名称,默认为系统默认数据库
	*/
	abstract public function useDatabase($dbName='');
	

	/**
	*获取表的列表
	*@return 表的数组;
	*/
	abstract protected function getTables($force=false,$database=null);
	
	
	/**
	*获取表的可执行sql;
	*@param string $table -表名
	*@param boolean $onlyStd-是否只反回结构
	*@return string sql语句
	*/
	abstract public function getTableSql($table,$onlyStd=false);

	/**
	 * 获取表的字段
	 * @param string $table 表名
	 * @return array
	 */
	abstract public function getTableFields($table);
	
	
	/**
	 * 获取当前的数据库类型
	 * @return string
	 */
	public function getTypeName(){
		return $this->dbTypeName;
	}
	
	
	/**
	 * 显示操作错误
	 * @param string  $msg 错误消息
	 * @param int $level  错误级别
	 */
	protected static function error($e,$level,$code=0){
		website::error($e,$level,2,$code);
	}	
	
	
}