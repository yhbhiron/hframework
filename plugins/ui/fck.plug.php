<?php
/**
 * ui模板插件fck编辑器，插件示例
 * <{fck name='fck' data=xx width=120 height=130 }>
 * 文件名规则： 插件名.plug.php
 * 函数命名：插件名PluginUI
 * 插件中可以使用已有的标签
 * @param string $code 模板源码
 * @param $ui 模板对象 必须
 * @return 必须返回$code
 */
function fckPluginUI($code,&$ui){
	
	$tag = $ui->getTagInfo('fck',$code);
	if($tag!=null){
		if($tag['name']==''){
			$ui->error('fck参数name不能为空！',1);
			return $code;
		}
		
		if($tag['toolbar']==''){
			$ui->error('fck参数toolbar不能为空！',1);
			return $code;
		}
		
		$name   = $tag['name'];
		$data   = StrObj::def($tag['data'],"''");
		$width  = $tag['width'];
		$height = $tag['height'];
		$toolbar = $tag['toolbar'];
		
		$code = '
		<?php 
			if(!defined(\'IN_EDITOR\')){
				include(WEB_LIB_DIR."editor/fckeditor.php");
				define(\'IN_EDITOR\',\'\');
				define(\'EDITOR_URL\',filer::getVisitURL(WEB_LIB_DIR."editor/"));
			}
			
			$fck = new FCKeditor('.$name.');
			$fck->BasePath= EDITOR_URL;
			$fck->ToolbarSet='.$toolbar.'; 
			
			$fck->Value = '.$data.';
			$fck->Width  = '.StrObj::def($width,'"100%"').';
			$fck->Height = '.StrObj::def($height,'"100%"').';
		
			$fck->Create();
			unset($fck);
		?>
		';

				
		
	}
	
	return $code;
}