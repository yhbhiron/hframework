<?php
/**
 * ui模板插件radio组，插件示例
 * <{radio name='chk' data=$xx  seldata=$data break=5 }>
 * 文件名规则： 插件名.plug.php
 * 函数命名：插件名PluginUI
 * 插件中可以使用已有的标签
 * @param string $code 模板源码
 * @param $ui 模板对象 必须
 * @return 必须返回$code
 */
function radioPluginUI($code,&$ui){
	
	$tag = $ui->getTagInfo('radio',$code);
	if($tag!=null){
		if(arrayObj::getItem($tag,'name')==''){
			$ui->error('radio参数name不能为空！',1);
			return $code;
		}
		
		if(arrayObj::getItem($tag,'data')==''){
			$ui->error('radio参数data不能为空！',1);
			return $code;
		}
		
		$name   = $tag['name'];
		$def   = arrayObj::getItem($tag,'seldata',"''");
		$data  = $tag['data'];
		$break =  arrayObj::getItem($tag,'break',0);
		
		$code = '
			
			<{foreach key=chkkey item=chkitem name=chkitem from='.$data.'}>
				<label style="margin-right:15px">
				<input type="radio"  name='.$name.' value="<{$chkkey}>" <{if '.$def.'==$chkkey }>checked<{/if}> ><{$chkitem}> 
				</label>
				<{if '.$break.'>0 && $hiron.foreach.chkitem.iteration % '.$break.'== 0 }>
				<br />
				<{/if}>
			<{/foreach}>
			
		';

				
		
	}
	
	return $code;
}