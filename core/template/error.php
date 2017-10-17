<?php !defined('IN_WEB') && exit('Access Deny!');
if($errorInfo == null){
	return;
}
if(in_array(website::$env,array(website::ENV_CLI,website::ENV_TEST_FORM))){
	
	echo  $errorInfo['type_name'].';'.$errorInfo['level_name'].$errorInfo['code'].$errorInfo['message'].website::$break;
	foreach($errorInfo['trace'] as $k=>$each){
		
		echo implode('; ',$each['err_info']).website::$break;
		if(arrayObj::getItem($each,'err_detail')!=null){
			foreach($each['err_detail'] as $j=>$line){
				echo $j.'------'.StrObj::stripTags($line['code']).website::$break;
			}
		}
	}
	
}else{
?>
<p style="color:#ff6600">
	<?php echo $errorInfo['type_name']?>:
	<?php echo $errorInfo['level_name'] ?> 
	#<?php echo $errorInfo['code'] ?>: 
	<?php echo $errorInfo['message'] ?>
</p>
<?php
foreach($errorInfo['trace'] as $k=>$each){
?>
<p><font color="red" size="2"><?php echo implode('; ',$each['err_info']) ?></font>
<?php if(arrayObj::getItem($each,'err_detail')!=null){ 
	
?>
<div style="background:#eeeeee;border:solid 1px;padding:5px;font-size:12px;">
<?php 
		foreach($each['err_detail'] as $j=>$line){
?>
	<div <?php if($line['selected']){ ?>style='background:#ff6500;color:#ffffff'<?php }?> > <?php echo $j ?> <?php echo $line['code'] ?></div>
<?php 			
		}
?>
</div>
<?php } ?>
</p>
<?php } ?>
<?php } ?>