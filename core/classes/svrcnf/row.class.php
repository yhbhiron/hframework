<?php !defined('IN_WEB') && exit('Access Deny!');
class svrCnfRow extends model{
	
	protected  $name;
	
	protected  $value;
	
	protected $comment;
	
	protected $quote = false;
	
	protected $delimer = '=';
	
	protected $rowEnd = '';
	
	protected $allowValues = array();
	
	/**
	 * 相关父配置
	 * @var serverConfig
	 */
	public  $cfgParent;
	
	
	/**
	 * 相关父块
	 * @var svrCnfBlock::
	 */
	public $cfgBlock;
	
	
	
	
	/**
	 * 相关默认值
	 * @var string
	 */
	protected $defaultValue = '';
	
	public function __construct($name,$value,$comment=''){
		$this->name = $name;
		$this->value = $value;
		$this->comment = $comment;
	}
	
	
	public function getName(){
		return $this->name;
	}
	
	
	public function setDelimer($delimer){
		
		$this->delimer = $delimer;
		return $this;
	}
	
	public function setEnd($end){
		
		$this->rowEnd = $end;
		return $this;
	}

	
	public function setQuote($on){
		$this-> quote = $on;
		return $this;
	}
	
	
	public function getString(){
		
		$this->value = validate::isNotEmpty($this->value) == false ? $this->defaultValue : $this->value;
		if($this->allowValues!=null && !in_array($this->value,$this->allowValues)){
			return false;
		}
		
		$this->value = $this->quote == true ? StrObj::addStrLR($this->value, '"') : $this->value;
		$out =  $this->name.$this->delimer.$this->value.$this->rowEnd;
		if($this->comment!=''){
			is_object($this->cfgParent) && $out.= ' '.$this->cfgParent->getCommentPrefix().$this->comment;
			is_object($this->cfgBlock)  && $out.= ' '.$this->cfgBlock->cfgParent->getCommentPrefix().$this->comment;
		}
		
		return $out;
	}
	
	
	public function __toString(){
		return $this->getString();
	}
	
}
