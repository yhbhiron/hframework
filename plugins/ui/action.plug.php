<?php

function actionPluginUI($code,&$ui){
	
	$tag = $ui->getTagInfo('action',$code);
	if($tag!=null){
		
		$needAct = arrayObj::getItem($tag,'name');
		if(Validate::isNotEmpty( $needAct ) == false){
			$ui->error('action参数name不能为空!');
			return $code;
		}
		
		$where = '';
		if( strstr($needAct,'|') ){
			$action = explode('|',$ui->trimQuote($needAct));
			$where  = ' in_array($act,'.preg_replace('/\s/','',var_export($action,true)).')';
		}else{
			$where = ' $act == '.$needAct;
		}
		
		$code ="<{if $where }>";
	}
	
	$code = preg_replace('/<{\s*\/action\s*}>/i',"<{/if}>",$code);
	return $code;
	
}