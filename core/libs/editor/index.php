<?php 
require_once('fckeditor.php') ;

$sBasePath="editor/";
$oFCKeditor = new FCKeditor('FCKeditor1') ;
$oFCKeditor->BasePath	= $sBasePath ;
$oFCKeditor->Value=$content;
if ($_GET['toolbar'] == 'Basic') {
	$oFCKeditor->ToolbarSet = 'Basic';
}
$oFCKeditor->Create() ;
?>