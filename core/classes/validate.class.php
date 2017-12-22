<?php 
if(!defined('IN_WEB')){
	exit;
}

/**
 * 常用验证
 * @since 2012-6-18
 * @author Jack Hiron
 * @version 1.0
 */
 class Validate{
 	
 	/**
 	 * 判断是否为空
 	 * @param mixed $mixed
 	 * return true/false 
 	 */
 	public static function isNotEmpty($mixed,$isArray=false){
 		
 		if(!$isArray && !is_array($mixed)){
 			
 			return trim($mixed)!='' && $mixed != null ;
 			
 		}else{
 			
 			return  $mixed != null && is_array($mixed) ;
 		}
 	}
 	
 	
 	/**
 	 * 判断是否为电话号码
 	 * @param string $str;
 	 * return true/false
 	 */
 	public static function isPhone($str){
 		
 		return self::isCollection($str) == false && preg_match('/^\d{7,}$/',$str);
 	}
 	
  	/**
 	 * 判断是否为QQ号码
 	 * @param string $str;
 	 * @return true/false
 	 */
 	public static function isQQ($str){
 		
 		return self::isCollection($str) == false && preg_match('/^\d{4,}$/',$str);
 	} 	
 	
 	/**
 	 * 判断是否为邮件地址
 	 * @param string $str;
 	 * return true/false
 	 */
 	public static function isEmail($str){
 		return self::isCollection($str) == false && preg_match('/^[a-z0-9A-Z_]+@{1}([a-z0-9A-Z_]+\.)+[a-z0-9A-Z]{2,5}$/',$str);
 	} 	
 	
 	
 	/**
 	 * 判断是否为整数
 	 * @param string $str;
	 * @param boolean $unsigned 是否为无符号
	 * @param boolean $nozero 是否大于零的数,可选，默认false
 	 * return true/false
 	 */
 	public static function isInt($str,$unsigned=false,$nozero=false){
 		if($unsigned ==false){	
 			return self::isCollection($str) == false && preg_match('/^\d+$/',$str);
		}else {
			return self::isCollection($str) == false && preg_match('/^\d+$/',$str) && ($nozero ? $str>0 : $str>=0);	
		}
 	}
 	
 	
 	/**
 	 * 判断是否为钱
 	 * @param string $str;
 	 * return true/false
 	 */
 	public static function isMoney($str){
 				
 		return self::isCollection($str) == false && is_numeric($str) && $str>0;
 	}
 	
 	/**
 	 * 判断是否为身份证号码
 	 * @param string $idcode;
 	 * return true/false
 	 */
 	public static function isIDcode($idcode){
 		
 		return self::isCollection($idcode) == false && preg_match('/\^d{17}[0-9x]$/i',$idcode);
 	}
 	
 	
 	/**
 	 * 判断是否为可显示内容的html代码
 	 * @param string $code html代码
 	 * return true/false
 	 */
 	public static function isNotEmptyHtml($code){
		return ($code!='' && (strip_tags($code)=='' && preg_match('/<img/i',$code))) 
		|| ($code!='' && strip_tags($code)!=''); 		
		 	
 	}
 	
 	
 	/**
 	 * 是否为url
 	 * @param string $str url字符串
 	 * @return boolean true/false
 	 */
 	public static function isURL($str){
 		
 		return self::isCollection($str) == false && preg_match('/^(ftp|http|https):\/\/.+/i',$str);
 		
 	}
 	
 	/**
 	 * 是否为英文
 	 * @param string $str
 	 */
 	public static function isEn($str){
 		return self::isCollection($str) == false && preg_match('/^[a-z\s]+/i',$str);
 	}
 	
 	
 	/**
 	 * 是否为文件目录
 	 * @param string $str
 	 */
 	public static function isDirName($str){
 		return self::isCollection($str) == false && preg_match('/^[a-z_\-0-9]+/i',$str);
 	}
 	
 	
 	/**
 	 * 判断是否为安全字符,即输出无特殊转化的字符
 	 * @param string $str
 	 * return true/false
 	 */
 	public static function isSafeStr($str){
 		return self::isCollection($str) == false && !preg_match('/\s[\'\"<>&]/',$str);
 	}
 	
 	/**
 	 * 判断是否为json
 	 * @param string $str 字符串
 	 * @return true/false
 	 */
 	public static function isJson($str){
 		return self::isCollection($str) == false && is_array(json_decode($str,true));
 	}
 	
 	/**
 	 * 不超过字符字数
 	 * @param string $str
 	 * @param int $len 字付个数
 	 */
 	public static function maxLength($str,$len){
 		return self::isCollection($str) == false && StrObj::count($str)<=$len;
 	}

 	 /**
 	 * 不小于字符字数
 	 * @param string $str
 	 * @param int $len 字付个数
 	 */
 	public static function minLength($str,$len){
 		return self::isCollection($str) == false && StrObj::count($str)>=$len;
 	}
 	
 	/**
	 * 是否为集合的类型
	 * @param $need
	 */
	public static function isCollection($need){
		return is_array($need) || is_resource($need) || is_object($need);
	} 	
	
	
	/**
	 * 是否为某种时间格式的字符
	 * @param string $time 时间字符 2012-12-15 等
	 * @param string  $format  date格式字符
	 */
	public static function isTimeFormat($time,$format){
		return self::isCollection($time) == false && date($format,strtotime($time)) == $time;
	}
 }

?>