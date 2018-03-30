<?php !defined('IN_WEB') && exit('Access Deny!');
class dataBaseSolr extends db{
	
	protected $dbTypeName = 'solr';
	
	protected function connect(){
		
		if($this->isConnected()){
			return $this->con;
		}
		
		if(!class_exists('SolrClient',false)){
			$this->error('未安装solr扩展',2);
		}
		
		$config = $this->config;
		$options = array(
			'hostname'=>$config['host'],
			'port'=>ArrayObj::getItem($config,'port','8983'),
			'login'=>ArrayObj::getItem($config,'user'),
			'password'=>ArrayObj::getItem($config,'passwd'),
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
    {
        return false;   
    }

    public function recordRows($query)
    {
                
    }

    public function query($query){
        
        if($query == null){
            return false;
        }
        
       
        if($query instanceof  SolrQuery){
           $response =  $this->con->query($query);
           Website::debugAdd('Solr请求地址：'.$response->getRequestUrl().'&'.$response->getRawRequest());
           $result = $response->getResponse();
        }else if($query instanceof  SolrInputDocument){
            $result = $this->addDocument($query);
        }else{
            $result = $this->request($query);
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
        $this->con->rollback();
    }

    public function getResArray($query, $single = false, $rowCallback = null){
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

    public function __call($method,$args){
        
        try{
            $resp = call_user_func_array(array($this->con,$method), $args);
        }catch(Exception $e){
            $this->error($e->getMessage(),2,$e->getCode());
            return false;
        }
        
        $args['response'] = $resp;
        website::doEvent('database.solr.'.strtolower($method),$args);
        return $resp->success();
    }
	
}
