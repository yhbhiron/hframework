<?php
if(!defined('IN_WEB')){
	exit;
}

/**
 * 本类主要是提供字符串处理函数
 * @author Hiron Jack
 * @since 2013-7-24
 * @version 1.0.0
 * @example $error = new error();
 * $error->showError('用户错误',2,1);
 */
class StrObj{
	
	const RND_NUM = 1;
	const RND_MIXED = 2;
	const RND_LOWER = 3;
	const RND_UPPER = 4;
	
	/**
	 * 用于缓存获取可能类名路径,减少重复调用
	 */
	private static $classPath = array();
	
	/**
	 * 添加不存的字符到指定字符的后面
	 * 如查找地址&时，不存在加?,存在则
	 * @param string  $str 查找字符范围
	 * @param string  $after 查要追加的字符
	 * @param string $notStr 找不到时需要追加的字符
	 * @return string 处理后的字符
	 */
	public static function addNotHasStrR($str,$after,$notStr=''){
		
		if(!strstr($str,$after)){
			return $str.$notStr;
		}		
		
		return $str.$after;
		
	}
	
	/**
	 * 添加不存的字符到指定字符的前面
	 * @param string  $str 查找字符范围
	 * @param string  $after 查要追加的字符
	 * @param string  $notStr 找不到时需要追加的字符
	 * @return string 处理后的字符
	 */
	public static function addNotHasStrL($str,$after,$notStr=''){
		
		if(!strstr($str,$after)){
			return $str;
		}		
		
		return $notStr.$after.$str;
		
	}	
	
	
	/**
	 * 给字符串两边加字符串
	 * @param string $str 需要操作的字符串
	 * @param string $add 追加的字符串
	 */
	public static function addStrLR($str,$add){
		return $add.$str.$add;
	}
	
	
	public static function addStrR($str,$padstr){
		
		if(self::right($str,strlen($padstr)!=$padstr)){
			return $str.$padstr;
		}
		
		return $str;
	}
	
	
	public static function left($str,$len){
		return substr($str,0,$len);
	}
	
	
	public static function right($str,$len){
		return substr($str,-$len);
	}	
	
	/**
	 * 转义gpc魔术引号
	 * @param mixed $string 需要转义的字符
	 * @param boolean $force 是否需要强制转化,如果没有开启魔术引号
	 * @return 转化后的字符
	 */
	public static function haddslashes($string,$force=false){
		
		if(!get_magic_quotes_gpc() || $force) {
			if(is_array($string)) {
				foreach($string as $key => $val) {
					$string[$key] = self::haddslashes($val, $force);
				}
			} else {
				$string = addslashes($string);
			}
		}
		
		return $string;
		
	}
	
	
	/**
	 * 从开始位置截取一段字符,用于utf8
	 * @param string $str 需要处理的字符串
	 * @param int $len 长度
	 * @param int $type 截取模式 1宽度模式、2-个数模式,默认为宽度模式 
	 * @param string $after截取后字符追加字符,可选
	 * @return string 截取后的字符
	 */
	public static function substr($str,$len,$type=1,$after=''){
		
		$strlen=strlen($str);
		if($len>$strlen){
			return $str;
		}
		
		$str = iconv('utf-8','gb2312',$str);		
		$useLen = 0;
		$tmpstr = '';
		
		for($i=0;$i<$strlen;$i++)
		{
			if(ord(substr($str,$i,1))>0xa0 )
			{
				$tmpstr.=substr($str,$i,2);
				$i++;
				if($type == 1){
					$useLen+=2;
				}else{
					$useLen++;	
				}
			}
			else
			{

				$tmpstr.=substr($str,$i,1);
				$useLen++;
			 }
			 
			 
			 
			 if($useLen>=$len || $useLen>=$strlen){
			 	break;
			 }
		}

		if($tmpstr!=$str && $after!=''){
			$tmpstr.=$after;
		}
		return iconv('gbk','utf-8',$tmpstr);
	}
	
	/**
	 * 返回字符串个数，适用于utf8字符
	 * @param string $str 计算的字符
	 * @return int 个数
	 */
	public static function count($str){
		
		$len=0;
		$strlen = strlen($str);
		for($i=0;$i<=$strlen;$i++){
			if(ord(substr($str,$i,1))>0xa0){
				$i+=2;
			}
			$len++;
		}
		return $len-1;
		
	}
	

	/**
	 * salt加密
	 * @param string $pwd 需要加密的字符
	 * @param string $sec 密匙
	 * @return string 加密后的字符
	 */
	public static function saltEncode($pwd,$sec=''){
			
		if($sec=='' && website::$config['secret']==null){
			
			return false;	
		}
		
		$sec = $sec == '' ? website::$config['secret'] : $sec;
		return md5($sec.$pwd.$sec);
		
	}
	
	/**
	 * shal加密
	 * @param string $pwd 需要加密的字符
	 * @param string $sec 密匙
	 * @return string 加密后的字符
	 */
	public static function shaEncode($pwd,$sec=''){
			
		if($sec=='' && website::$config['sec']==null){
			
			return false;	
		}
		
		$sec = $sec == '' ? SYS_SEC : website::$config['sec'];
		return sha1($sec.$pwd.$sec);
		
	}	
	
	/**
	 * 随机字符
	 * @param string $len字符长度
	 * @param string $type 字符类型：Mixed 混合、Num-数字、Lower小写字母、Upper大写字符
	 * @return string 返回随机字符
	 */
	public static function randStr($len=11,$type=1){
		
		$type = static::def($type,self::RND_NUM);
		
		if($type==self::RND_NUM){
			$rand='1342507869';
		}else if($type==self::RND_LOWER){
			$rand='abcdefghijklmnopqrstuvwxyz';
		}else if($type==self::RND_UPPER){
			$rand='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		}else if($type==self::RND_MIXED){
			$rand='01jPUklm!o2QT3ac4eGHpGKL_Mq67hiDE@8r-RSstVu59vwWXzABCxdyFbIfgNOYZ';
		}
		
		$len = intval($len);
		$str = '';
		for($i =1;$i<=$len;$i++){
			$str.=substr($rand,rand(0,strlen($rand)-1),1);
		}
		
		return $str;
	}
	
	/**
	 * 转换字符为html案全字符
	 * @param string $str 需要转化的字符
	 * @return string 转化后的字符
	 */
	public static function toSafeHtml($str){
		
		if(is_array($str)){
			
			foreach($str as $key=>$value){
				$new[$key] = self::toSafeHtml($value);
			}
			
		}else{
			
			$new = htmlspecialchars($str,ENT_QUOTES);
			
		}
		
		return trim($new);
	}
	
	
	/**
	 * 设置字符的默认值,当有$case参数时，如果$val!=$case则使用$def,默认为与空进行比较
	 * @param string $val 查找的字符串
	 * @param string $def 黙认值
	 * @param string $case 比较值，可选
	 */
	public static function def($val,$def,$case=''){
	
		if($case!=''){
			if($val!=$case){
				return $def;
			}
		}
		
		if($val==''){
			return $def;
		}
		
		return $val;
		
		
	}
	
	/**
	 * 把带有.或下划线的字符变成abcAbc类型的类名
	 * @param string $virtualName 虚拟名称
	 * @return string
	 */
	public static function getClassName($virtualName){
		
		if(strstr($virtualName,'.')){
			return lcfirst(implode('',array_map(function($v){ return ucfirst($v); },explode('.',$virtualName))));
		}else if(strstr($virtualName,'_')){
			return lcfirst(implode('',array_map(function($v){ return ucfirst($v); },explode('_',$virtualName))));
		}
		
		return $virtualName;
	}
	
	
	/**
	 * 把峰驼型类名转化成用分隔符分开的字符
	 * @param string $className 类名
	 * @param string $delimer 分隔符，可选，默认 _
	 */
	public static function delimerClassName($className,$delimer='_'){
		
		if(!validate::isNotEmpty($className)){
			return false;
		}
		
		$spaceName = preg_replace('/(?<=[^A-Z])([A-Z])/',' $1',$className);
		$list = preg_split('/ /',$spaceName);
		
		return implode('_',$list);
		
	}
	
	/**
	 * 获取一个类名的可能路径
	 * @param string $className
	 * @return array();
	 */
	public static function getClassPath($className){
		
		if(isset(self::$classPath[$className])){
			return self::$classPath[$className];
		}
		
		$spaceName = preg_replace('/(?<=[^A-Z])([A-Z])/',' $1',$className);
		$list = preg_split('/ /',$spaceName);
		if($list == null){
			return $list;
		}
		
		$len = count($list) - 1;
		if($len == 0){
			$list[0] = strtolower($list[0]);
			return $list;
		}
		
		$group = array();
		foreach($list as $k=>$f){
			
 			for($i=$k+1;$i<=$len;$i++){
 				
 				$first = array_slice($list,1,$i);
 				$second = array_slice($list,$i+1);
 				
 				$group[] = trim(strtolower($f.implode('',$first).'/'.implode('/',$second)),'/');
 				$group[] = trim(strtolower($f.'/'.implode('',$first).'/'.implode('/',$second)),'/');
 				$group[] = trim(strtolower($f.implode('',$first).'/'.implode('',$second)),'/');
 				$group[] = trim(strtolower($f.'/'.implode('',$first).'/'.implode('',$second)),'/');
 			}
 			
 			break;
		}
		
		return self::$classPath[$className] = array_unique($group);
		
	}
	
	
	
	
	public static function hencode($str){
		
		if($str == ''){
			return;
		}
		
		$sec = website::$config['secret'];
		$len = strlen($str)-1;
		$secLen = strlen($sec)-1;
		$new = '';
		for($i=0;$i<=$len;$i++){
			$tmp = $str[$i];
			for($j=0;$j<=$secLen;$j++){
				$tmp = $tmp.$sec[$j];
				
			}

			$new.= base64_encode($tmp);
		}
		
		return base64_encode($new);
	}
	
	
	
	/**
	 * 测试两个字符串是符相似
	 * @param string $text1 字符一
	 * @param string $text2 符符二
	 * @param float $sameRate 相似度
	 * @return true/false 
	 */
	public static function isSameText($text1,$text2,$sameRate=0.8){
		
		if(trim($text1) == trim($text2)){
			return true;
		}
		
		if($text1 == '' || $text2 == ''){
			return false;
		}
		
		$filter = '/\?|？|\.|。|,|，|\!|！|\(|\)/';
		
		$text1 = preg_replace($filter,'',strip_tags($text1));
		$text2 = preg_replace($filter,'',$text2);
		
		$strlen = strlen($text1);
		$g1     = array();
		for($i=0;$i<=$strlen;$i++){
			if(ord(substr($text1,$i,1))>0xa0){
				$char = substr($text1,$i,3);
				$i+=2;
			}else{
				$char = substr($text1,$i,1);
			}
			
			if(trim($char)!='' && !isset($g1[$char])){
				$g1[$char] = substr_count($text1,$char);
			}
		}
		
		$strlen = strlen($text2);
		$g2     = array();
		for($i=0;$i<=$strlen;$i++){
			if(ord(substr($text2,$i,1))>0xa0){
				$char = substr($text2,$i,3);
				$i+=2;
			}else{
				$char = substr($text2,$i,1);
			}
			
			if(trim($char)!='' && !isset($g2[$char])){
				$g2[$char] = substr_count($text2,$char);
			}
		}		
		
		$rate = 0;
		foreach($g1 as $k=>$t){
			
			if(isset($g2[$k])){
			    $rate++;
			    /**
				$min = min($g2[$k],$t);
				$max = max($g2[$k],$t);
				if( $min / $max >= $sameRate){
					$rate++;
				}**/
			}
			
		}
		
		if($rate / count($g1) >= $sameRate && $rate / count($g2) >= $sameRate){
			return true;
		}
		
		return false;
		
		
	}
	
	/**
	 * 是否为utf8字符
	 * @param string $str
	 * @return boolean
	 */
	public static function isUtf8($str){
		
		if(function_exists('mb_detect_encoding')){
				
			$encode = strtolower( mb_detect_encoding($str, array('UTF-8','GB2312','GBK')) );
			return $encode == 'utf-8';
				
		}
		
		$str = preg_replace('/\n|\r|\s/','',strip_tags($str));
		if($str == ''){
			return true;
		}
		
		return preg_match('/^(?:
		[\x09\x0A\x0D\x20-\x7E] # ASCII
		| [\xC2-\xDF][\x80-\xBF] # non-overlong 2-byte
		| \xE0[\xA0-\xBF][\x80-\xBF] # excluding overlongs
		| [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
		| \xED[\x80-\x9F][\x80-\xBF] # excluding surrogates
		| \xF0[\x90-\xBF][\x80-\xBF]{2} # planes 1-3
		| [\xF1-\xF3][\x80-\xBF]{3} # planes 4-15
		| \xF4[\x80-\x8F][\x80-\xBF]{2} # plane 16
		)*$/xs', $str);
	}
	
	
	/**
	 * 安全级的explode
	 * @param string $delimer 分隔字符
	 * @param string $haystack 需要分隔的字符
	 * @return array;
	 */
	public static function explode($delimer,$haystack){
		
		if(validate::isNotEmpty($haystack) == false){
			return array();
		}
		
		return explode($delimer,$haystack);
	}
	
	
	/**
	 * 输出一段字符到cli控制台
	 * @param string $text
	 */
	public function WriteCli($text){
		fwrite(STDOUT,$text.PHP_EOL);
	}
	
	
	
	/**
	 * 根据平台输出字符换行
	 * @param string $str
	 */
	public static function  secho($str){
		
		$cli = php_sapi_name() == 'cli'; 
		$br  = website::$break;
		if($cli){
			if(PHP_OS == 'WINNT'){
				echo iconv('utf-8','gbk//ignore',$str).$br;
				return;
			}
		}
				
		echo $str.$br;
		
	}	
	
	
	public static function  pinyinFirst($s0){
		
		if(preg_match('/[a-z]/i',substr($s0,0,1))){
			return $s0{0};
		}
		
	    $fchar = ord($s0{0});
	    if($fchar >= ord("A") and $fchar <= ord("z") ){
	   		return strtoupper($s0{0});
	    }
	    
	    $s1 = iconv("UTF-8","gbk//ignore", $s0);
	    $s2 = iconv("gb2312","UTF-8//ignore", $s1);
	    if($s2 == $s0){
	    	$s = $s1;
	   	}else{
	   		$s = $s0;
	   	}
	   	
	    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
	    if($asc >= -20319 and $asc <= -20284) return "A";
	    if($asc >= -20283 and $asc <= -19776) return "B";
	    if($asc >= -19775 and $asc <= -19219) return "C";
	    if($asc >= -19218 and $asc <= -18711) return "D";
	    if($asc >= -18710 and $asc <= -18527) return "E";
	    if($asc >= -18526 and $asc <= -18240) return "F";
	    if($asc >= -18239 and $asc <= -17923) return "G";
	    if($asc >= -17922 and $asc <= -17418) return "H";
	    if($asc >= -17922 and $asc <= -17418) return "I";
	    if($asc >= -17417 and $asc <= -16475) return "J";
	    if($asc >= -16474 and $asc <= -16213) return "K";
	    if($asc >= -16212 and $asc <= -15641) return "L";
	    if($asc >= -15640 and $asc <= -15166) return "M";
	    if($asc >= -15165 and $asc <= -14923) return "N";
	    if($asc >= -14922 and $asc <= -14915) return "O";
	    if($asc >= -14914 and $asc <= -14631) return "P";
	    if($asc >= -14630 and $asc <= -14150) return "Q";
	    if($asc >= -14149 and $asc <= -14091) return "R";
	    if($asc >= -14090 and $asc <= -13319) return "S";
	    if($asc >= -13318 and $asc <= -12839) return "T";
	    if($asc >= -12838 and $asc <= -12557) return "W";
	    if($asc >= -12556 and $asc <= -11848) return "X";
	    if($asc >= -11847 and $asc <= -11056) return "Y";
	    if($asc >= -11055 and $asc <= -10247) return "Z";
	    return NULL;
	} 
	
	
	/**
	 * 却除html，包括空格
	 * @param string $html  HTML代码
	 * @return string
	 */
	public static function striptags($html){
		
		if($html == null){
			return $html;
		}
		
		if(is_array($html)){
			foreach($html as $k=>$code){
				$html[$k] = self::striptags($code);
			}
		}else{
			$html = preg_replace('/&nbsp;/i','',strip_tags($html));
		}
		
		return $html;
	}
	
	
	/**
	 * 将一段原样的格式输出html
	 * @param string $str
	 * @return string
	 */
	public static function toHtmlCode($str){
		
		if(validate::isNotEmpty($str) == false){
			return $str;
		}
		
		$str = preg_replace('/\n\r/','<br>',$str);
		$str = preg_replace('/\t/','&nbsp;&nbsp;&nbsp;&nbsp;',$str);
		$str = preg_replace('/\s/','&nbsp;&nbsp;',$str);
		
		
		return $str;
	}
	
	
	/**
	 * 转化数组或字符的编码
	 * @param array/string $data
	 * @param string $inCharset 输入字符集
	 * @param string  $outCharset　输出字符集
	 * @return mixed
	 */
	public static function conv($data,$inCharset,$outCharset){
		
		if(is_array($data)){
			foreach($data as $k=>$v){
				$data[$k] = self::conv($v,$inCharset,$outCharset);
			}
		}else{
			$data = iconv($inCharset,$outCharset,$data);
		}
		
		return $data;
	}

	
	/**
	 * 把ip转换成整型
	 * @param string $ip ip地址字符
	 * @return
	 */
	public static function ipToInt($ip){
		
		if(function_exists('ip2long')){
			return ip2long($ip);
		}
		
		$list = static::explode('.', $ip);
		if($list == null){
			return 0;
		}
		
		$list = array_reverse($list);
		$int = 0;
		foreach($list as $k=>$v){
			$int+=$v<<($k*8);
		}

		return $int;
	}
	
	
	
    public static function escape_string($str){
        
        if(function_exists('mysql_escape_string')){
            return mysql_escape_string($str);
        }

        if (function_exists('mb_ereg_replace'))
        {
                $str = mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $str);
        } else {
                $str =  preg_replace('~[\x00\x0A\x0D\x1A\x22\x27\x5C]~u', '\\\$0', $str);
        }
        
        return $str;
    }
	
}
?>