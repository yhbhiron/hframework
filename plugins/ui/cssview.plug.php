<?php

$plugIsGlobal = true;
function cssviewPluginUI($code,&$ui){

	$code = preg_replace_callback('/<link([^>]+)href=\"([^"]+)\"([^>]*)\/*>/i',
		function($m){
		    if(!Validate::isURL($m[2]) && !preg_match('/no-auto-css=\"yes\"/i',$m[0])
		        && (preg_match('/rel=\"stylesheet\"/i',$m[1]) or preg_match('/rel=\"stylesheet\"/i',$m[3])) ){
			     $m[2] = preg_replace('/<\{(.+)\}>/','".$1."',$m[2]);
			     return '<{css href="'.$m[2].'" }>';
		    }
		    
		    return $m[0];
		},
		$code
	);
	
	
	$code = preg_replace_callback('/<script[^>]+src=\"([^"]+)\"[^>]*>\s*?<\/script>/i',
		function($m){
			$m[1] = preg_replace('/<{|}>/','',$m[1]);
			return '<{script src="'.$m[1].'" }>';
		},	
		
		$code
	);
	
	return $code;

}

?>