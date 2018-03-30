<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * solr查询器
 * @example
 * 插入数据
 * $d = DB::instance('solr-test');
 * $r = QuerySolr::factory()->reset()->insert(array(
        'goods_id'=>369,
        'goods_name'=>'test',
        'goods_search_words'=>'test',
   ))->execute($d);
   print_r($r);
 * 
 * 
 * 
 * @author Administrator
 *
 */
class QuerySolr extends Query{
    
    Const COND_OR  = ' OR ';
    Const COND_AND = ' AND ';
    Const COND_XOR = ' XOR ';
    Const COND_NOT = ' NOT ';
    
    protected  $query = null;
    
    public function __construct(){
        $this->reset();
    }
    
    

    /**
     * 获取solr查询对象
     * @return SolrQuery
     */
    protected function getSolrObject(){
        $query =  $this->query['query_object']  ? $this->query['query_object'] : new SolrQuery();
        return  $query;
    }
    
    public function reset(){
        $this->query['query_str'] = 
        $this->query['cmd'] = 
        $this->query['query_object'] = null;
        return $this;
    }
    
    public function select(array $items){
        if($items == null){
            return $this;
        }
        
        $query =  $this->getSolrObject();
        $query->setQuery('lucene');
        foreach($items as $name){
            $query->addField($name);
        }
        
        $this->query['query_object'] = $query;
        return $this;
    }
    
    
    /**
     * 排序 sort
     * @param array $order array('fld'=>'desc')
     * @return QuerySolr
     */
    public function sortBy(array $order){
        
        if($order == null){
            return $this;
        }
        
        $query =  $this->getSolrObject();
        $tmp = array();
        foreach($order as $k=>$d){
            
            if(is_numeric($k)){
                $query->addSortField($d);
            }else{
                $query->addSortField($d,arrayObj::getRightVal($d,array('desc','asc'),'asc'));
            }
        }
            
        $this->query['query_object'] = $query;
        
        return $this;
    }
    
    
    public function page($page,$size){
        
        $page = max(intval($page),1);
        $size = max(intval($size),1);
        $limit = $size;
        $offset = ($page-1)*$size;
        
        $query =  $this->getSolrObject();
        $query->setStart($offset);
        $query->setRows($size);
        $this->query['query_object'] = $query;
        
        
        return $this;
        
    }
    
    
    /**
     * 增加记录
     * @param array $items array(字段名=>值)
     * @return boolean
     */
    public function insert(array $items){
        
        if($items == null){
            return false;
        }
        
        $doc = new SolrInputDocument();
        foreach($items as $fld=>$val){
            $doc->addField($fld, $val);
        }
        
        
        $this->query['query_object'] = $doc;
        return $this;
        
    }
    
    
    /**
     * 更新记录，引用insert
     * @param array $items array(字段名称=>值)
     * @return boolean
     */
    public function update(array $items){
        return $this->insert($items);
    }
    
    
    
    /**
     * 删除记录
     * @return QuerySolr
     */
    public function delete(){
        $this->query['cmd'] = 'deleteByQuery';
        $this->query['query_object'] = 'SolrClient';
        return $this;
    }
    
    
    
    /**
     * 等于条件
     * ? a?b 匹配acb,aeb
     * * a*b 匹配acb,aeffb,esss..b
     * ~ ab~ 匹配词语
     * [1 TO n] 匹配数据范围
     * ab^n 配配一个词并设置权重:"ab"^120
     * @param array $flds
     * @param string $group 组合条件罗辑
     * @return QuerySolr
     */
    public function whereEq($flds,$group=null){
        
        if(!validate::isNotEmpty($flds,true)){
            return $this;
        }
        
        $this->buildWhere($flds,':',false,$group);
        return $this;
    }
    
    

    public function setCondition($key,$val,$relation,$logic){
        
        if($this->query['query_str']!=null && !in_array(trim(end($this->query['query_str'])),array('(') ) ){
            $temp =" $logic ".$key." $relation $val ";
        }else{
            $temp =" ".$key." $relation $val ";
        }
        
        $this->query['query_str'][] =  $temp;
        
        
    }
    
    
    /**
     * 组建条件
     * @param array $flds 条件设置数组由各个条件函数提供
     * array( array(fld=>字段名,req请求名或值,logic逻辑,to_url是否加入地址) ),
     * @param string $relation 条件关系，由各个条件函数提供
     * @param boolean $quote 值是否需要加引号
     * @param array $group 是条件组信息
     */
    protected function buildWhere($flds,$relation,$quote,$group){
        
        $groupStr ='';
        foreach($flds as $k=>$conf){
            
            $key   = arrayObj::getItem($conf,'fld',arrayObj::getItem($conf,0));
            $logic = arrayObj::getItem($conf,'logic',arrayObj::getItem($conf,2,self::COND_AND));
            $val   = arrayObj::getItem($conf,'val',arrayObj::getItem($conf,1));
            
            /**条件值转化***/
            if(is_array($val)){
                
                $val = array_map(function($v){ $v = StrObj::escape_string($v);return "'$v'";},$val);
                $val = '('.implode(',',$val).')';
                
            }else if(preg_match('/`[a-z0-9_\.]+`/i',$val)){
                $val = trim($val,'`');
            }else{
                
                $val   = StrObj::escape_string($val);
                if($quote == true){
                    $val = "'$val'";
                }
                
            }
            
            if($val!=''){
                
                $oldVal = $val;
                
                if($group==null){
                    
                    $this->setCondition($key,$val,$relation,$logic);
                    
                }else{
                    
                    if($groupStr==''){
                        $logic ='';
                        
                    }
                    $groupStr.= " $logic $key $relation $val ";
                    
                }
                
                
            }
        }//eof
        
        if($group!=null && $groupStr!=null){
            $this->setCondition('','('.$groupStr.')','',$group);
        }
    }
    
    
    public function execute($driver=null,$callback=null){
        
        if($driver == null){
            $driver = db::instance('solr');
        }
        
        $this->parseQuery();
        
        $object = ArrayObj::getItem($this->query,'query_object');
        $params = ArrayObj::getItem($this->query,'params',array());
        if($object == 'SolrClient'){
            $res =  call_user_func_array(array($driver, $this->query['cmd']),$params);
        }else{
            $res =  $driver->query($object);
        }
        
        $driver->commit();
        return $res;
    }
    
    
    public function parseQuery(){
        
        $queryStr = ArrayObj::getItem($this->query,'query_str')!=null ? implode(' ',$this->query['query_str']) : '';
        $cmd = ArrayObj::getItem($this->query,'cmd');
        $qObject =  ArrayObj::getItem($this->query,'query_object');
        !isset($this->query['params']) && $this->query['params'] = array();
        
        if($cmd == 'deleteByQuery'){
            $this->query['params'][] = $queryStr;
        }else if($qObject instanceof  SolrQuery){
            $queryStr!='' && $this->query['query_object']->setQuery($queryStr);
        }
        
        return null;
        
    }
    
    
    public function  __toString(){
       return null;
    }
   
}
