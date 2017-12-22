<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 数据库队列
 * 表结构:id,code,addtime,status
 * @author Administrator
 */
class queueDb extends queue{
	
	
	public static $model = 'queue';
	
	public  function enter($code){
		return ORM::factory(self::$model)->add(
			array(
				'code'=>$code,
				'addtime'=>time(),
			)
		);
	}
	
	
	public function execute($q){
		
		$success = '';
		try{
			
			$func = create_function('', $q->code);
			@$func();
			$success = '成功!';
			
		}catch(Exception $e){
			$success = '失败!';
		}
		
		StrObj::secho('执行队列'.$q->id.'------'.$success);
	}
	
	
	
	public  function listen(){
	
		$table = ORM::factory(self::$model)->tableName();
		$db = DB::instance();
		$db->start();
		$db->query('SET @update_id:=0');
		Query::factory()->update(array('status'=>2,'id'=>'`(select @update_id:=id)`'))
		->from($table)
		->whereEq(array(
		    array('status','1')
		))->limit(1)->execute($db);
		
		$id = arrayObj::getItem($db->getResArray('select @update_id',true),'@update_id');
		if($id<=0){
			return;
		}
		
		$q = query::factory()->select('id')->from($table)
		->whereEq(
			array(
				array('fld'=>'id','val'=>$id),
			)
		)->orderby(array('addtime'=>'asc'))->limit(1)->execute();
		
		$q = arrayObj::getItem($q, 0,array());
		$model  = ORM::factory(self::$model,$q['id']);
		if($model->exists() == false){
			return false;
		}

		$model->remove();
		$db->commit();
		if($q != null){
			$this->execute($model);
		}
		
		
	}
	
	
	
	
}
 