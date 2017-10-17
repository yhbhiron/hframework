<?php !defined('IN_WEB') && exit('Access Deny!');
class cliQueueEvent extends eventAction{
	
	public function run(){
		
		$qtype = request::get('queue_type');
		if($qtype == ''){
			return;
		}
		
		queue::factory($qtype)->listen();
		exit;
	}
	
	
}
