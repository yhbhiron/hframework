<?php !defined('IN_WEB') && exit('Access Deny!');
class queueRedis extends queue{
	
	protected $redis;
	
	protected $listName = 'hiron_redis_queue';
	
	public function __construct(){
		$this->redis = new cacheRedis();
	}
	
	public  function enter($code){
		return $this->redis->rpush($this->listName,$code);	
	}
	
	public function execute($queue){
		
		$success = '';
		try{
			
			$func = create_function('', $queue);
			$func();
			$success = 'Success!';
			
		}catch(Exception $e){
			$success = 'Failed!--'.$e->getMessage();
		}

		StrObj::secho(time::now().':Execute Queue '.$success);
	}
	
	public  function listen(){
		
		$code = $this->redis->lpop($this->listName);
		if($code == ''){
			return;
		}
		
		$this->execute($code);
	}
}
