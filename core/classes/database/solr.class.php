<?php !defined('IN_WEB') && exit('Access Deny!');
class dataBaseSolr extends db{
	
	protected $dbTypeName = 'solr';
	
	protected function connect(){
		
		if($this->isConnected()){
			return $this->con;
		}
		
		if(!class_exists('SolrClient',false)){
			$this->error('未安装solr扩展');
		}
		
		$config = $this->config;
		$options = array(
			'hostname'=>$config['db_host'],
			'port'=>arrayObj::getItem($config,'port','8983'),
			'login'=>arrayObj::getItem($config,'user'),
			'password'=>arrayObj::getItem($config,'passwd'),
			'path'=>$config['database'],
			'wt'=>'xml',
		);
		
		$this->con = new SolrClient($options);
		self::$connected[$this->config['db_key']][$this->config['db_host']] = $this;
	}
	
	
	protected function isConnected(){
		
		if(validate::isCollection($this->con)){
			try{
			
				$result = $this->con->ping();
				if($result instanceof  SolrPingResponse){
					return true;	
				}else{
					return false;
				}
				
			}catch(Exception $e){
				return false;
			}
		}
		
		return false;
	}
	
	
    public function recordSum($query){
        
    }

    public function useDatabase($dbName = '')
    {}

    public function recordRows($query)
    {
        
        
    }

    public function query($query){
        
        if($query == null){
            return false;
        }
        
        if($query instanceof  SolrQuery){
           $result =  $this->con->query($query);
        }else if($query instanceof  SolrInputDocument){
            $result = $this->con->addDocument($query);
        }else{
            $result = $this->con->request($query);
        }
        
        return $result;
    }

    public function getTableInfo($table){
    	return array();
    }

    public function start()
    {
        return false;
    }

    public function commit()
    {
        $this->con->commit();
    }

    public function getTableSql($table, $onlyStd = false)
    {
        return null;
    }

    public function getTableFields($table)
    {
        return array();
        
    }

    public function rollBack()
    {
        $this->con->rollBack();
    }

    public function getResArray(SolrQuery $query, $single = false, $rowCallback = null){
        $result = $this->query($query);
        
    }

    public function lastInsertID(){
    	return 0;
    }

    protected function getTables($force = false, $database = null){
    	return array();
    }

    public function affectedRows(){
    	return 0;
    }

    public function close(){
    
    }

	
}
