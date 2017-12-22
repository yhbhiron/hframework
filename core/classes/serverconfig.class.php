<?php !defined('IN_WEB') && exit('Access Deny!');
class ServerConfig extends Model{
	
	protected  $cfgList = array();
	
	protected $commentPrefix = '#';
	
	
	public function setCommentPrefix($prefix){
		$this->commentPrefix = $prefix;
		return $this;
	}
	

	public function getCommentPrefix(){
		return $this->commentPrefix;
	}
	
	public function addBlock(svrCnfBlock $block){
		
		$block->cfgParent = $this;
		$this->cfgList[] = $block;
		return $this;
	}
	
	
	
	public function addRow(svrCnfRow $row){
		
		$row->cfgParent = $this;
		$this->cfgList[] = $row;
		return $this;
	}
	
	public function addCmd($cmd){
		$this->cfgList[] = $cmd;
		return $this;
	}
	
	
	public function getString(){
		
		$out = '';
		if($this->cfgList!=null){
			foreach($this->cfgList as $k=>$cfg){
				if( ($cfg instanceof svrCnfBlock) ||  ($cfg instanceof svrCnfBlock)){
					$out.=$cfg->getString()."\r\n";
				}else{
					$out.=$cfg."\r\n";
				}
			}
		}
		
		
		return $out;
		
	}
	
	
	public function __toString(){
		return $this->getString();
	}
	
}