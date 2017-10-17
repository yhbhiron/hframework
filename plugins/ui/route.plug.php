<?php !defined('IN_WEB') && exit('Access Deny!');
function routePluginUI($code,&$ui){
	
	$tag = $ui->getTagInfo('route',$code);
	if($tag == null){
		return $code;
	}
	
	$key = arrayObj::getItem($tag,'key');
	unset($tag['key'],$tag['tpl_tag_name']);
	if($key == null){
		$ui->error('route插件,参数key不能为空!',2);
		return $code;
	}
	
	
	$params = '';
	if($tag!=null){
		
		$params = ',array(';
		foreach($tag as $k=>$v){
			$params.="'$k'=>$v,";
		}
		
		$params.=")";
	}
	
	
	return '<?php echo website::$route->getURL('.$key.$params.'); ?>';
	
	
}
