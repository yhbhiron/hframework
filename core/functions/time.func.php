<?php
/**
 * 本类主要是时间处理函数
 * @author: Hiron Jack
 * @since 2013-7-23
 * @version: 1.0.1
 * @example:
 */

class Time{

	
	/**
	 * 反回当前时间
	 * @return string
	 */
	public static function now($format=''){
		if($format == ''){
			return date('Y-m-d H:i:s');
		}
		
		return date($format);
		
	}
	
	
	public static function def($time,$format,$def){
		if($time<=0){
			return $def;
		}
		
		return date($format,$time);
	}
	
	/**
	获取指定时间的年月日时分秒;
	@param $time string 
	@param $formateStr string 格式符
	@return 时间字符串
	*/
	public static function timeFormat($time,$formatStr){
		
		if($time==''){
			
			return self::now($formatStr);
			
		}else if(is_string($time) && !is_numeric($time)){
			
			$time = strtotime($time);
		}
		
		return date($formatStr,$time);	
	}	
	
	
	/**
	计算两个时间的时间差
	@param time1 string 时间1
	@param time2 string 时间2
	@param unit 单位值 y,m,d,h,i,s,w,all分别是：年月日时分秒周,所有
	@return  int/arrau 以时间单位的整数,当unit为all时，返回数组array(d,h,i,s);
	**/
	public static function getDistance($time1,$time2,$unit='d'){
		
		$time1 = strtotime($time1);
		$time2 = strtotime($time2);
		
		$second = 1;
		if($unit == 'd'){
			$second = 86400;	
		}else if($unit == 'm'){
			$second = 30*24*3600;	
		}else if($unit == 'y'){
			$second = 365*24*3600;		
		}else if($unit == 'h'){
			$second = 3600;		
		}else if($unit == 'i'){
			$second = 60;			
		}else if($unit == 'w'){
			$second = 86400*7;
		}else if($unit == 'all'){
			
			$item = array();
			$left = abs($time1-$time2);
			$item['d'] = intval(($left)/86400);
			$item['h'] = intval( (($left)/3600) % 24);
			$item['i'] = intval((($left)/60)%60);
			$item['s'] = intval($left%60);
			
			return $item;
		}
		
		return abs(round(($time1-$time2)/$second,0));
			
	}
	
	public static function gmtime() {
		return (time () - date ( 'Z' ));
	}	
}




?>