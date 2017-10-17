<?php

$plugIsGlobal = true;
function cssviewPluginUI($code,&$ui){

	$code = preg_replace_callback('/<link[^>]+href=\"\.\.\/(?:style|css|js|script)\/([^"]+)\"[^>]*\/*>/i',
		function($m){
			$m[1] = preg_replace('/<\{(.+)\}>/','".$1."',$m[1]);
			return '<{css href="'.$m[1].'" }>';
		},
		$code
	);
	
	
	$code = preg_replace_callback('/<script[^>]+src=\"\.\.\/(?:style|css|js|script)\/([^"]+)\"[^>]*>\s*?<\/script>/i',
		function($m){
			$m[1] = preg_replace('/<{|}>/','',$m[1]);
			return '<{script src="'.$m[1].'" }>';
		},	
		
		$code
	);
	
	return $code;

}

?>