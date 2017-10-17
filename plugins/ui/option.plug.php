<?php
/**
 * ui模板插件option，插件示例
 * <{option date=$xx default='' title='' name='' }>
 * 文件名规则： 插件名.plug.php
 * 函数命名：插件名PluginUI
 * 插件中可以使用已有的标签
 * @param string $code 模板源码
 * @param $ui 模板对象 必须
 * @return 必须返回$code
 */
function optionPluginUI($code,&$ui){
	
	$tag = $ui->getTagInfo('option',$code);
	if($tag!=null){
		if(arrayObj::getItem($tag,'data')==''){
			$ui->error('option参数data不能为空！',1);
			return $code;
		}
		
		$def   = arrayObj::getItem($tag,'default') == '' ? "''" : $tag['default'];
		$title = str_replace('+',' ',trim(arrayObj::getItem($tag,'title'),'"'));
		$title = $title!='' ? '<option value="">'.$title.'</option>' : '';
		$name  = arrayObj::getItem($tag,'name');
		$data  = $tag['data'];
		$class = StrObj::def(str_replace('+',' ',arrayObj::getItem($tag,'class')),'""');
		
		$code = '
			<select name='.$name.' id='.$name.' class='.$class.'>
			'.$title.'
			<{foreach key=opkey item=opitem name=opitem from='.$data.'}>
			<option value="<{$opkey}>" <{if $opkey=='.$def.'  && '.$def.'!="" }>selected<{/if}> ><{$opitem}></option>
			<{/foreach}>
			</select>
		';
		
		
	}
	
	return $code;
}