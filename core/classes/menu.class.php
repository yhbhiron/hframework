<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 本类主要是菜单基类
 * @author Hiron Jack
 * @since 2017-10-09
 * @version 2.0.2
 * @example:
 */

class menu extends ORM{

	/**直接父类的字段名**/
	protected  $fkey     = 'father';
	
	/**所有相关父类的字段名称**/
	protected  $afkey    = 'allfather';
	
	/**相关模型表**/
	protected  $mtable  =  'memu';
	
	
	/**
	 * 
	 * @param int $id 菜单id，可选
	 */	
	public function __construct($id=0){
		parent::__construct($this->mtable,$id);
	}
	
	
	/**
	 * 获取某一菜单的子菜单
	 * @param boolean $all 是否获取全部，为false时只获取第一级的子菜单，为true时获取该
	 * 菜单下的所有子类
	 * @return array 子菜单数组
	 */
	public function getChildren($all=false){

		if($this->id<=0){
			return false;
		}
		
		if(!$all){
		    
		    $q = new query();
			$chs = $q->select(array($this->modKey))->from($this->tableName())->whereEq(
				array(
				  array($this->fkey,$this->id)       
				)        
			)->execute($this->db());
			
		}else{
		    
		    $q = new query();
		    $chs = $q->select(array($this->modKey))->from($this->tableName())->whereFindInSet(
		            array(
		                array($this->afkey,$this->id)
		            )
		    )->execute($this->db());
		    
		}
		
		if($chs!=null){
			foreach($chs as $k=>$ch){
				$chs[$k] = new menu($ch);
			}
		}
		
		return $chs;
	}
	
	
	
	/**
	 * 某一菜单是否为当前菜单的子菜单，无论他是多少级
	 * @param int $cid 子菜单id
	 * @param boolean
	 */
	public function isChildOf($cid){
		
		if($this->exits() == false){
			return false;
		}
		
		$m     = new menu($cid);
		$fids  = $m->{$this->afkey};
		$father = StrObj::explode(',',$fids);
		
		return in_array($this->{$this->modKey},$father);
	}
	
	
	/**
	 * 获取菜单的层级
	 * @return number
	 */
	public function getLevel(){
	    if($this->exists() == false){
	        return 0;
	    }
	    
	    if($this->{$this->fkey} <=0){
	        return 1;
	    }
	    
	   return count(StrObj::explode(',', $this->{$this->afkey})+1);
	}
	
	
	/**
	 * 增加一个子类
	 * @param array $item 菜单信息
	 * @return true/false
	 */
	public function addChild($item){
		
		if($this->exists() == false){
			return false;
		}
		
		
		$afkey = $this->afkey;
		$item[$this->fkey]  = $this->id;
		if($this->$afkey!=''){
			$item[$afkey] = $this->$afkey.','.$this->id;
		}else{
			 $this->$afkey =  $this->get($afkey);
			 $item[$afkey] =  $this->$afkey!='' ? $this->$afkey.','.$this->id : $this->id;
		}
		
		$menu = new self();
		return $menu->add($item);
	}
	
	
	/**
	 * 更新当前菜单
	 * @param array $item 菜单信息
	 * @return true/false
	 */
	public function edit($item){
		
		if($this->exists()==false || !validate::isNotEmpty($item,true)){
			return false;
		}
		
		$fid = $item[$this->fkey];
		if($fid>0 && $fid!=$this->get($this->fkey)){
			
			if($this->isChildOf($fid)){
				return false;
			}
			
			$father = new menu($fid);
			$item[$this->fkey]  = $this->{$this->modKey};
			$item[$this->afkey] = $father->get($this->afkey).','.$fid;
		}
		
		return parent::set($item);
	}
	
	
	/**
	 * 遍历一个菜单的所有子菜单,并应用到一个回调函数
	 * @param string $callBack 回调函数
	 */
	public  function explore($callback){
		
		if($this->exists() == false){
			return false;
		}
		
		$children = $this->getChildren();
		if($children!=null){
			
			foreach($children as $key=>$m){
			
				if(is_callable($callback)){					
					$callback($m);					
				}
				
				$chM = $m->explore($callback);			
			
			}
					
		}
		
	}	
	
	
}