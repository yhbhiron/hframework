<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 本类主要是数字处理函数
 * @author: Hiron Jack
 * @since 2013-7-23
 * @version: 1.0.1
 * @example:
 */


class Number{
	
	
	/**
	 * 格式化价格
	 * @param float $price 价格
	 * @param int $decNum 小数位数,默认
	 * @param boolean $round 是否四舍五入,可选，默认不四舍五入
	 */
	public static function priceFormat($price,$decNum=2,$round=false){
		
		
		if($round == true){
			return round($price,$decNum);
		}
		
		if($price==0){
			return '0'.$decNum>0 ? '.'.str_repeat('0',$decNum) : '';	
		}
		
		if($decNum<=0){
			return intval($price);	
		}
		
		if(strstr($price,'.')){
			$speNum = explode('.',$price);
			$speNum[1] = isset($speNum[1][$decNum-1]) ? substr($speNum[1],0,$decNum) : str_pad($speNum[1],$decNum,0);
			
			return $speNum[0].'.'.$speNum[1];
		}
		
		
		return $price.'.'.str_repeat('0',$decNum);
		
	}	
	
}