<?php
/**
 * 数组操作函数库
 * 
 * @author Hiron Jack
 * @since 2013-9-18
 * @version 1.0.0
 * @example:
 */	
	class ArrayObj{
		
		/**
		 * 查找一个数组是否为别一数组的子集
		 * @param array $arr1 array(a,b,c,d)
		 * @param array $arr2 array(e,f,g,h)
		 * @reurtn true/false
		 */
		public static function arrInArray($arr1,$arr2){
			
			$sub = array_intersect($arr1,$arr2);
			return count($sub) == count($arr1);
			
			
		}
		
		/**
		 * 获取一个数组的指定key的数据项
		 * @param array $array 查找的数组 array(k=>v,k2=>v)
		 * @param array $mathKey 指定key值的数据 array(a,b,c,d)
		 * @param boolean $notNull 是否去掉空值的数项
		 * @return array 部分数据
		 */
		public static function getExistsItem($array,$mathKey,$notNull=false){
			
			if(!validate::isNotEmpty($array,true) || !validate::isNotEmpty($mathKey,true) ){
				return false;
			}
			
			foreach($array as $k=>$item){
				if(!in_array($k,$mathKey)){
					unset($array[$k]);
				}
				
				if($notNull && !validate::isNotEmpty(self::getItem($array,$k))){
					unset($array[$k]);
				}
			}
			
			return $array;
		}
		
		
		/**
		 * 使用数组中的数据，组件成新的hash数组key=>val形式
		 * @param array $arr 输入数组
		 * @param string $keyName 新数组的键值
		 * @param array/string $valName 新数组的值
		 * @param string $keyPre 链值前辍
		 */
		public static function toHashArray($arr,$keyName,$valName,$keyPre=''){
			
			if(!is_array($arr) ||$arr == null){
				return array();	
			}
			
			$temp = array();
			foreach($arr as $k=>$value){
				
				if(!is_array($valName)){
					$temp[$keyPre.$value[$keyName]] = $value[$valName];
				}else{
					
					$valArray = array();
					foreach($valName as $vk=>$val){
						
						$valArray[$val] = $value[$val];
					}
					
					$temp[$keyPre.$value[$keyName]] = $valArray;
					unset($valArray);
				}
			}
			
			return $temp;
			
			
		}
		
		
		/**
		 * 把两个数组按继承的方式合并
		 * @param array $uparams 新设置的数组
		 * @param array $defParams 默认设置的数组
		 * @return array
		 */
		public static function extend($uparams,$defParams){
			
			if($uparams == null && $defParams == null){
				return array();
			}
					
			if($uparams == null && $defParams!=null){
				return $defParams;
			}
			
			if($uparams !=null && $defParams == null){
				return $uparams;
			}
			

			
			foreach($defParams as $k=>$param){
				
				if(isset($uparams[$k]) && !is_numeric($k)){
					if( is_array($defParams[$k]) ){
						$defParams[$k] = self::extend($uparams[$k],$param);
					}else{
						$defParams[$k] = $uparams[$k];
					}
				}
				
			}
			
			foreach($uparams as $k=>$params){
				if(is_numeric($k)){
					$defParams[] = $params;
				}else if(!isset($defParams[$k])){
					$defParams[$k] = $params;
				}
			}
					
			return $defParams;		
		}		
		
		
		/**
		 * 获取一个数组中的某元素，不存在返回默认值
		 * @example
		 * arrayObj::getItem($a,'a');
		 * arrayObj::getItem($a,array('a','b','c') );
		 * @param array $arr 数组
		 * @param string/array $key 键名 ，可以使用数据获取数组下边的所有键
		 * @param mixed $def  不存时的,默认值
		 * @return mixed 
		 */
		public static function getItem($arr,$key,$def=null){
			
			if(!is_array($arr)){
				return $def!=null ? $def : $arr;
			}
			
			if(is_array($key)){
			    $last = $arr;
			    foreach($key as $k){
			        if(!isset($last[$k])){
			            return $def;
			        }else{
			            $last = $last[$k];
			        }
			    }
			    
			    return $last;
			}
			
			return !isset($arr[$key]) ? $def : $arr[$key];
		}
		
		
		/**
		 * 将数组中的元素合并成一个数组
		 * @param array $array,格式array( array(),array())转化成array( array(a1,a2),array(a1,a2))
		 * @return array;
		 */
		public static function itemMerge($array){

			if(!validate::isNotEmpty($array,true)){
				return $array;
			}
			
			$new = array();
			$copy = $array;
			foreach($array as $k=>$items){
				
				if(!validate::isNotEmpty($items,true)){
					return $array;
				}
				
				foreach($items as $j=>$val){
					$temp = array();
					$temp[$k] = $val;
					foreach($copy as $m=>$items2){
						foreach($items2 as $n=>$val2){
							if(strval($n) == strval($j) && strval($m)!=strval($k) ){
								$temp[$m] = $val2;
								break;
							}
						}
					}
					
					$new[] = $temp;
				}
				break;
			}
			
			return $new;
			
		}	
		
		
		/**
		 * 获取规定列表中的值，不合法时返回默认值
		 * @param string $val 检测值
		 * @param array $list 查找数组列表array(va1,va2,val3)
		 * @param string $def 找不到时返回的默认值
		 * @return string
		 */
		public static function getRightVal($val,$list,$def){
		
			if(in_array($val,$list)){
				return $val;
			}
			
			return $def;
		}

		
		/**
		 * 导出数组为字符串，美化格式化
		 * @param array $arr 需要导出的数组
		 * @return array
		 */
		public static function export(array $arr){
			
			if($arr == null){
				return $arr;
			}
			
			$str = var_export($arr,true);
			$str = preg_replace('/=>\s+array\s*\(/i','=>array(',$str);
			$str = preg_replace('/\d+\s*=>\s*array\(/i','array(',$str);
			
			return $str;
			
		}
		
		
		/**
		 * 过滤掉空的数据
		 * @param array $array
		 * @return array
		 */
		public static function trim(array $array){
			return array_filter($array,function($v){
				return $v!=null;
			}
			);
		}
		
		
		/**
		 * 随机获取数组的成员
		 * @param array  $arr　数组
		 * @param int $num 获取的数量,如果超过数组的长度，则返回随机位置的所有数据
		 * @param boolean $unset 是否去除获取到的成员
		 * @return array
		 */
		public static function getRandMember(array &$arr,$num,$unset=false){
			
			if($arr == null){
				return array();
			}
			
			$return = array();
			if($num == 1){
				$index = array_rand($arr,$num);
				$return[] = $arr[$index];
				if($unset){ 
					unset($arr[$index]); 
				}
				
			}else{
				
				$index = array_rand($arr,min(count($arr),$num));
				foreach($index as $k=>$i){
					
					$return[] = $arr[$i];
					if($unset){ 
						unset($arr[$i]); 
					}
					
				}
				
				
			}
			
			return $return;
			
		}
		
		
		/**
		 * 强制转化为一数组
		 * @param mixed $list 数据或数组
		 * @param boolean $forceEmpty 不是组时是否强制转化为数组
		 * @return array
		 */
		public static function forceToArray($list,$forceEmpty=false){
			
			if(validate::isNotEmpty($list,$forceEmpty) == false){
				return array();
			}
			
			return is_array($list) ? $list : array($list);
		}
		
		
		/**
		 * 通过回调修改一个数组的结构
		 * @param array $array 需要修改的数组
		 * @param callback $callback 应用的回调函数 callback($key键,$val值,$list修改后的数组列表）,返回 $list
		 * @return array
		 */
		public static function map(array $array,$callback){
			
			if(!validate::isNotEmpty($array) || !is_callable($callback)){
				return $array;
			}
			
			$newArray = array();
			foreach($array as $k=>$each){
				$newArray = call_user_func($callback,$k,$each,$newArray);
			}
			
			return $newArray;
			
		}
		
		
		/**
		 * 把array(k=>array(a=>1),k2=>array(a=>2))类型的数据按键分组
		 * @param array $array
		 * @param string $key 键名
		 * @return array
		 */
		public static function groupByKey(array $array,$key){
		    
		    if($array == null){
		        return array();
		    }
		    
		    return static::map($array,function($k,$v,$list)use($key){
		        
		        if(!isset($v[$key])){
		            return $list;
		        }
		        
				if(!isset($list[$v[$key]]) ){
				    $list[$v[$key]] = array();
				}
				
				$list[$v[$key]][] = $v;
				
				return $list;
		    });
		    
		}
		
		
		/**
		 * 按不同的序顺排列数组中的元素，元素不重复 [a,b,c]=>{a,b,c}{a,c,b}{b,a,c}....
		 * @param array $array 数组
		 * @return array
		 */
		public static function groupUniqueSortMember(array $array,$sub=array(),$depath=1){
			
			if($array == null){
				return array();
			}
			
			$len = count($array);
			if($len == 1){
				return array(0=>$array);
			}
			
			$list = array();
			if($depath == 1){
				foreach($array as $k=>$v){
					$temp = array();
					$temp[$k] = $v;
					$list = array_merge($list,static::groupUniqueSortMember($array,$temp,$depath+1));
				}
			}else if($depath == 2){
				
				$temp = array();
				foreach($array as $k=>$v){
					if(!isset($sub[$k])){
						$temp2 = $sub;
						$temp2[$k] = $v;
						$temp[] = $temp2;
					}
				}
				
				if($len == $depath){
					$list = $temp;
				}else{
					$list = static::groupUniqueSortMember($array,$temp,$depath+1);
				}
				
			}else{
				
				$temp = array();
				foreach($array as $k=>$v){
					foreach($sub as $m=>$n){
						if(!isset($n[$k])){
							$temp2 = $n;
							$temp2[$k] = $v;
							$temp[] = $temp2;
						}
					}
					
				}

				if($depath == $len){
					$list = $temp;
				}else{
					$list = static::groupUniqueSortMember($array,$temp,$depath+1);
				}
				
			}
						
			
			return $list;
			
		}
		
		
		/**
		 * 从数组中选择一个或多个元素，进行组合，分组中的元素可以改变顺序，各分组的元素不能重复
		 * [a,b,c]=>{a}{b}{c}{a,b}{a,c}{a,b,c}
		 * @param array $array
		 * @return array
		 */
		public static function groupSelectQuiqueMember(array $array,$sub=array(),$depath=1){
			
			if($array == null){
				return array();
			}
			
			$len = count($array);
			if($len == 1){
				return array(0=>$array);
			}
			
			$list = array();
			foreach($array as $k=>$m){
				
				if($sub!=null){
					
					$temp = $sub;
					if(!isset($sub[$k])){
						
						$temp[$k] = $m;
						$keys = array_keys($temp);
						sort($keys);
						$keyName = md5(implode(',',$keys) );
						if(!isset($list[$keyName])){
							$list[$keyName] = $temp;
						
							if($depath <=$len){
								$list = array_merge(self::groupSelectQuiqueMember($array,$temp,$depath+1),$list);
							}
						}
						
					}
					
					
				}else{
					$temp = array($k=>$m);
					$list[] = $temp;
					
					if($depath <=$len){
						$list = array_merge(self::groupSelectQuiqueMember($array,$temp,$depath+1),$list);
					}
					
				}
				
				
				
			}
			
			
			if($depath == 1){
				$list = array_values($list);
			}
			
			return $list;
			
		}
		
		

	}
	

	


?>