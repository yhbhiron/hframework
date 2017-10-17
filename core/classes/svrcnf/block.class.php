<?php !defined('IN_WEB') && exit('Access Deny!');
class svrCnfBlock extends  model{
	
	/**
	 * 块名称
	 * @var_type string
	 */
	protected $name;
	
	
	/**
	 * 块开始分隔符
	 * @var string
	 */
	protected $startDelimer = '';
	
	
	protected $comment;
	
	
	/**
	 * 块结束分隔符
	 * @var string
	 */	
	protected $endDelimer = '';
	
	
	protected $blockNameFormat = '[%s]';
	
	
	protected $cmds = array();
	
	
	protected $cfgRows = array();
	
	
	public $cfgParent;
	
	
	public function __construct($name,$comment=''){
		$this->name = $name;
		$this->comment = $comment;
	}
	
	
	public function getName(){
		return $this->name;
	}
	
	
	public function addCmd($cmd){
		$this->cmds[] = $cmd;
		return $this;
	}
	
	public function addRow(svrCnfRow $row){
		$row->cfgBlock = $this;
		$this->cfgRows[$row->getName()] = $row;
		return $this;
	}
	
	
	public function setNameFormat($format){
		
		$this->blockNameFormat = $format;
		return $this;
	}
	
	public function setStartDelimer($delimer){
		$this->startDelimer = $delimer;
		return $this;
	}
	

	public function setEndDelimer($delimer){
		$this->endDelimer = $delimer;
		return $this;
	}
	
	
	/**
	 * 获取字符
	 */
	public function getString(){
		
		$out='';
		if($this->comment!=''){
			$out.=$this->cfgParent->getCommentPrefix().$this->comment."\r\n";
		}

		$out.= sprintf($this->blockNameFormat,$this->name)."\r\n";
		if($this->startDelimer){
			$out.=$this->startDelimer."\r\n";
		}
		
		if($this->cmds!=null){
			foreach($this->cmds as $k=>$cmd){
				$out.='  '.$cmd."\r\n";
			}
		}
		
		if($this->cfgRows!=null){
			foreach($this->cfgRows as $k=>$row){
				$out.= '  '.$row->getString()."\r\n";
			}
		}
		
		if($this->endDelimer){
			$out.="\r\n".$this->endDelimer;
		}
		
		
		return $out;
	}
	
	
	public function __toString(){
		return $this->getString();
	}
	
}