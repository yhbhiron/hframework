<?php ?>
<div ondblclick="this.style.width='100%';this.style.height='200px'" style="opacity:0.9;padding:5px;font-size:12px;position:fixed;bottom:0;left:0;width:10%;background:#ffffff;z-index:99999;height:40px;border:solid 1px #dddddd;overflow-y:scroll">
	<a href="javascript:;" onclick="this.parentNode.style.width='100%';this.parentNode.style.height='200px'" style="position:absolute;top:4px;right:35px">展开</a>
	<a href="javascript:;" onclick="this.parentNode.style.display='none'" style="position:absolute;top:4px;right:4px">关闭</a>
	<h4>调试信息</h4>
	<p>
		<?php foreach($output as $k=>$o){ ?>
		<p><font color="blue"><?php echo $o ?></font></p>
		<?php } ?>
	</p>

</div>
