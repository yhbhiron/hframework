<?php
/**
 * ui模板插件checkbox组，插件示例
 * <{checkbox name='chk' data=$xx  seldata=$data break=5 }>
 * 文件名规则： 插件名.plug.php
 * 函数命名：插件名PluginUI
 * 插件中可以使用已有的标签
 * @param string $code 模板源码
 * @param $ui 模板对象 必须
 * @return 必须返回$code
 */
function checkboxPluginUI($code,&$ui){
	
	$tag = $ui->getTagInfo('checkbox',$code);
	if($tag!=null){
		if($tag['name']==''){
			$ui->error('checkbox参数name不能为空！',1);
			return $code;
		}
		
		if($tag['data']==''){
			$ui->error('checkbox参数data不能为空！',1);
			return $code;
		}
		
		$name   = $tag['name'];
		$def   = StrObj::def($tag['seldata'],"''");
		$data  = $tag['data'];
		$break =  StrObj::def($tag['break'],0);
		
		$code = '
			
			<{foreach key=chkkey item=chkitem name=chkitem from='.$data.'}>
				<label>
				<input type="checkbox"  name='.$name.' value="<{$chkkey}>" <{if (is_array('.$def.') && in_array($chkkey,'.$def.')) || '.$def.'==$chkkey }>checked<{/if}> ><{$chkitem}> 
				</label>
				<{if '.$break.'>0 && $hiron.foreach.chkitem.iteration % '.$break.'== 0 }>
				<br />
				<{/if}>
			<{/foreach}>
			
		';

				
		
	}
	
	return $code;
}