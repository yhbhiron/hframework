<?php !defined('IN_WEB') && exit('Access Deny!');
/**
数据查询组装类，用户组装各种复杂的查询
@name 查询组装器
@author jackbrown
@version 1.0.0
@time  2015-8-06
@example 
$query->select(array('id','title'=>'cat_name') )->from('guu_prodcat')->whereEq(array(
	array('fld'=>'a','val'=>'b','logic'=>query::COND_AND),
	array('fld'=>'c','val'=>'d','logic'=>query::COND_OR),
	array('fld'=>'e','val'=>'f','logic'=>query::COND_AND),
),query::COND_AND)->limit(1,2);
		
$a = query::factory()->select('a')->from('b');
$b = query::factory()->select('c')->from('d');

$query->union($a,$b)->distinct()->select(array('id','name'))->from('user')->useIndex('user_type')
->leftJoin('info','s')
->using('user_id')
->leftJoin('address','ad')
->on(array(
	array('cd','`ef`',query::COND_AND,'whereEq'),
))		
->rightJoin('score','sc')
->on(array(
	array('ghg','`efx`',query::COND_AND,'whereEq'),
))
->whereGroup(
	array(
		array('id','23',query::COND_AND,'whereEq'),
		array('user_name','16',query::COND_AND,'whereEq'),
		array(
			array('user_email','23',query::COND_OR,'whereEq'),
			array('user_mobile','23',query::COND_OR,'whereEq'),
		),
		array(
			array('user_money',0,query::COND_OR,'whereMaxEq'),
			array('user_score',0,query::COND_OR,'whereMaxEq'),
		)				
	),
	
	query::COND_AND
)->whereEq(array(
	array('fld'=>'a','val'=>':b','logic'=>query::COND_AND),
	array('fld'=>'c','val'=>':d','logic'=>query::COND_AND),
),query::COND_AND)->param(':b',"xxsfd'")->having('a','>=',3);
*/
abstract class Query extends Model{
	
    const COND_OR  = ' OR ';
    const COND_AND = ' AND ';
    const COND_XOR = ' XOR ';
    const COND_NOT = ' NOT ';
    
    /**默认方式*/
    private static $default = 'mysql';
	
	/**
	 * 创建query
	 * @param string $query
	 * @return query
	 */
    public static function factory($query=null){
        
        if(validate::isNotEmpty($query) == true){
            $class   = 'Query'.ucfirst($query);
        }else{
            if(get_called_class() != 'Query'){
                return new static();
            }else{
                $class   = 'Query'.ucfirst(self::$default);
            }
        }
        
        return new $class();
    }

	
	/**
	 * 重置所有条件
	 * @return Query
	 */
	abstract public function reset();
	

	
	
	/**
	 * 分页
	 * @param int $page 当前页
	 * @param int $size 大小
	 * @return query
	 */
	abstract public function page($page,$size);

	
	
	
	/**
	 * 执行
	 * @param db/string $driver 数据连接对象,不填刚采用默认的连接
	 * @param callback $rowCallback 对每行执行回调
	 * @return array
	 */
	abstract public function execute($driver=null,$rowCallback=null);
	

	
	protected function error($msg){
		website::error($msg,2,2);
	}
	
} 