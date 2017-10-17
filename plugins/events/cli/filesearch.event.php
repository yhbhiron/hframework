<?php !defined('IN_WEB') && exit('Access Deny!');
class cliFileSearchEvent extends eventAction{
	
	public function run(){
		
		$searchName = request::get('sfile');
		if($searchName == ''){
			return;
		}
		
		$root  =  StrObj::def(request::get('sdir'),WEB_ROOT);
		StrObj::secho('文件查找中...');
		filer::scandir($root,function($path)use($searchName){
		 	
			 $allowExt = array('php','js','css');
			 $ext = substr(strrchr($path,'.'),1);
			 if(!in_array($ext,$allowExt)){
				 return;
			 }
			 
			 $path = realpath($path);
			 $code = file_get_contents($path);
			 if(strstr($code,$searchName) || strstr($path,$searchName)){
			 	StrObj::secho($path);
			 }
		 	
		 });
		 
		 StrObj::secho('文件查找完成');
		 exit;
	}
	
}
