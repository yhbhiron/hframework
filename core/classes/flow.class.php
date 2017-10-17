<?php
class workflow{
	
	protected $passData;
	
	/**规则列表**/
	protected $rules;
	
	
	protected $status = 1;
	
	const FLOW_STATUS_STOP     = 0;
	const FLOW_STATUS_RUNNING  = 1;
	const FLOW_STATUS_FINISHED = 2;
	
	
	public function __construct($data,$rules){
		
		$this->passData = $data;
		$this->rules    = $rules;
		
	}
	
	
	public function run(){
		
		$flow = $this->getCurrent();
		
	}
	
	
	public function createRules(){
		
		if(validate::isNotEmpty($this->rules) == false){
			$this->status = self::FLOW_STATUS_STOP;
			return false;
		}
		
		
		foreach($this->rules as $k=>$rule){
			
		}
		
	}
	
	
	public function getCurrent(){
		
	}
	
	
	public function getLast(){
		
	}
	
	
	public function getNext(){
		
	}
	
	
	
}