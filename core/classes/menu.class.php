<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 本类主要是菜单基类
 * @author Hiron Jack
 * @since 2017-10-09
 * @version 2.0.2
 * @example:
 */

class Menu extends ORM{
    
    /**直接父类的字段名**/
    protected  $fkey     = 'father';
    
    /**所有相关父类的字段名称**/
    protected  $afkey    = 'allfather';
    
    /**是否有子类的字段名*/
    protected  $hasChKey    = 'has_child';
    
    /**相关模型表**/
    protected  $mtable  =  'memu';
    
    const TYPE_OBJECT = 1;
    
    const TYPE_ASSOC = 2;
    
    
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
    public function getChildren($all=false,$type=self::TYPE_ASSOC){
        
        if($this->id<=0){
            return false;
        }
        
        if(!$all){
            
            $q = Query::factory();
            $chs = $q->from($this->tableName())->whereEq(
                array(
                    array($this->fkey,$this->id)
                )
                )->execute($this->db());
                
        }else{
            
            $q = Query::factory();
            $chs = $q->from($this->tableName())->whereFindInSet(
                array(
                    array($this->afkey,$this->id)
                )
                )->execute($this->db());
                
        }
        
        if($type == self::TYPE_OBJECT){
            if($chs!=null){
                foreach($chs as $k=>$ch){
                    $chs[$k] = new self($ch);
                }
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
        
        if($this->exists() == false){
            return false;
        }
        
        $m     = new static($cid);
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
        $res = true;
        $res = $menu->add($item);
        $res = $res && $this->edit(array(
            $this->hasChKey=>1,
        ));
        
        return $res;
    }
    
    /**
     * 增加分类
     * @param array $item 菜单信息
     * @return true/false
     */
    public function add(array $item){
        
        $item = $this->getParentItem($item);
        if(!$item){
            return false;
        }
        
        return parent::add($item);
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
        
        $item = $this->getParentItem($item);
        if(!$item){
            return false;
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
    
    
    /**
     * 获取整个菜单树
     * @param number $fid
     * @return array|unknown
     */
    public  function getMenuTree($fid=0){
        
        $list = Query::factory()->reset()->from($this->modTable)
        ->whereEq(array(
            array($this->fkey,$fid)
        ))->execute($this->db());
        
        
        if($list == null){
            return array();
        }
        
        foreach($list as $k=>&$cat){
            if($cat[$this->hasChKey] == 1){
                $cat['children'] = $this->getMenuTree($cat[$this->modKey]);
            }else{
                $cat['children'] = array();
            }
            
            $cat['cat_level'] = $cat[$this->afkey]<=0 ? 1 : count(StrObj::explode(',', $cat[$this->afkey]))+1;
        }
        
        return $list;
    }
    
    
    public  function treeToHash($tree){
        
        $hash = array();
        foreach($tree as $k=>$list){
            
            $temp = $list;
            unset($temp['children']);
            $hash[$list[$this->modKey]]  = $temp;
            if($list['children']!=null){
                $subList = $this->treeToHash($list['children']);
                $hash = $hash+$subList;
            }
        }
        
        
        return $hash;
        
    }
    
    
    public function remove(){
        
        $item = $this->toArray();
        $res = parent::remove();
        if($res){
            $this->getParentItem($item);
        }
        
        return $res;
    }
    
    
    /**
     * 给项目加父类信息
     * @param array $item
     * @return array
     */
    protected function getParentItem($item){
        
        $fid = ArrayObj::getItem($item,$this->fkey);
        $setFid = isset($item[$this->fkey]);
        if($fid>0){
            
            $fatherChanged = true;
            if($this->exists()){
                
                if($this->isChildOf($fid)){
                    return false;
                }
                
                if($this->{$this->fkey}!=$fid){
                    
                    $oldFather = new static($this->{$this->fkey});
                    $fatherChildCount = count($oldFather->getChildren());
                    $has = $this->{$this->fkey}>0 ? intval($fatherChildCount-1>0) : intval($fatherChildCount+1>0);
                    $oldFather->edit(
                        array($this->hasChKey=>$has)
                        );
                    
                }else{
                    $fatherChanged = false;
                }
                
            }
            
            
            if($fatherChanged){
                
                $father = new static($fid);
                if($father->exists() == false){
                    return false;
                }
                
                $afkey = $father->get($this->afkey);
                $item[$this->fkey]  = $fid;
                $item[$this->afkey] = $afkey!='' ? $father->get($this->afkey).','.$fid :  $fid;
                $father->edit(array(
                    $this->hasChKey=>1,
                ));
                
            }
            
        }else if($this->exists()){
            
            if($setFid){
                if($this->{$this->fkey}>0){
                    $oldFather = new static($this->{$this->fkey});
                    $fatherChildCount =count($oldFather->getChildren());
                    $has = intval($fatherChildCount-1>0);
                    $oldFather->edit(
                        array($this->hasChKey=>$has)
                        );
                }
                
                $item[$this->fkey] =0;
                $item[$this->afkey] = '';
            }
            
        }
        
        return $item;
    }
    
    
}