<?php

if(!defined('IN_WEB')){
 exit;
}
/**
 * 名称=>array(
 * 	'from_uri'=>'伪静地址正则、function方法、array(地址规则一，地址规则二)',
 *  'url_to'=>数组 array(
 *  	'act'=>'行为名称，或正则组名称,如$1,如组一或名称一，从from_uri中规则中确定组名',
 *  	'app'=>'应用控制器，或正则组名称,如$1,如组一或名称一，从from_uri中规则中确定组名',
 *  	'status'=>'可选，http的状态,200,301待',
 *  	'url'=>'重定向地址,如果设置了他，则act,system,app设置无效，路由会重定向这个地址',
 *  	
 *  )或function(匹配项)该方法需要设置GET['act'] 和  GET['app'] 的值,
 *  'get_url'=>function(geturi信息的回调,配置键名,其它参数1，其它参数2){
 *      
 *   },
 * )
 */
return array (

    'default' => array(
        'sort' => '-1',
        'from_uri' =>array('([a-z]+)_([a-z_]+)?','([a-z]+)?'),
        'get_uri' => function ($callback, $key, $app,$act,$params = array()) {
            $app = lcfirst($app);
            return website::$route->buildUrlParams(website::$url['host'] .$key, $params);
        },
        
        'url_to'=>function($match){
            
            $path = StrObj::explode('_',$match[0]);
            $len = count($path);
            if($len<=2){
                request::get('app',$path[0]);
                request::get('act',ArrayObj::getItem($path,1));
                return;
            }
            
            $act = '';
            while($path!=null){
                
                $act = array_pop($path).$act;
                $class = array_reduce($path,function($v1,$v2){
                    return ucfirst($v1).ucfirst($v2);
                });
                
                    
                if(class_exists($class.'App',true)){
                    request::get('app',$class);
                    request::get('act',$act);
                    break;
                }
            }
        
        }
        
   ),
) ;
?>