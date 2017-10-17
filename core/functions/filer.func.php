<?php
if(!defined('IN_WEB')){
	exit;
}
/**
 * 本类主要是文件处理函数
 * @author: Hiron Jack
 * @since 2013-7-23
 * @version: 1.0.1
 * @example:
 */

class filer{
	
	/**允许上传的文件格式*/
	public static $allowFormat = array('jpg','jpeg','png','gif');
	
	/**允许上传的最大尺寸*/
	public static $uploadSizeLimit = 0;
	
	/**是否为api模式，用于api文件同步*/
	public static $apiMode = false;
	
	/**http curl返回的响应头*/
	public static $httpHeader = array();
	
	/***http请求的agent可以修改*/
	public static $userAgent = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:36.0) Gecko/20100101 Firefox/36.0';
	
	public static $cookieFile = '';
	
	/**
	 * 请求头信息
	 * @var array
	 */
	public static $header = array();
	
	/**文扩展名对应的mime类型表**/
	protected static $mimes = array(
		
			'323'=>'text/h323','acx'=>'application/internet-property-stream','ai'=>'application/postscript',
			'aif'=>'audio/x-aiff','aifc'=>'audio/x-aiff','aiff'=>'audio/x-aiff','asf'=>'video/x-ms-asf',
			'asr'=>'video/x-ms-asf','asx'=>'video/x-ms-asf','au'=>'audio/basic','avi'=>'video/x-msvideo','axs'=>'application/olescript',
			'bas'=>'text/plain','bcpio'=>'application/x-bcpio','bin'=>'application/octet-stream','bmp'=>'image/bmp',
			'c'=>'text/plain','cat'=>'application/vnd.ms-pkiseccat','cdf'=>'application/x-cdf','cer'=>'application/x-x509-ca-cert',
			'class'=>'application/octet-stream','clp'=>'application/x-msclip','cmx'=>'image/x-cmx','cod'=>'image/cis-cod',
			'cpio'=>'application/x-cpio','crd'=>'application/x-mscardfile','crl'=>'application/pkix-crl','crt'=>'application/x-x509-ca-cert',
			'csh'=>'application/x-csh','css'=>'text/css','csv'=>'text/comma-separated-values',
		
			'dcr'=>'application/x-director','der'=>'application/x-x509-ca-cert',
			'dir'=>'application/x-director','dll'=>'application/x-msdownload','dms'=>'application/octet-stream',
			'doc'=>'application/msword','dot'=>'application/msword','dvi'=>'application/x-dvi','dxr'=>'application/x-director',
			'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			
			'eps'=>'application/postscript','etx'=>'text/x-setext','evy'=>'application/envoy','exe'=>'application/octet-stream',
			'fif'=>'application/fractals','flr'=>'x-world/x-vrml',
			
			'gif'=>'image/gif','gtar'=>'application/x-gtar','gz'=>'application/x-gzip',
			
			'h'=>'text/plain','hdf'=>'application/x-hdf','hlp'=>'application/winhlp',
			'hqx'=>'application/mac-binhex40','hta'=>'application/hta','htc'=>'text/x-component',
			'htm'=>'text/html','html'=>'text/html','htt'=>'text/webviewhtml',
			
			'ico'=>'image/x-icon','ief'=>'image/ief','iii'=>'application/x-iphone',
			'ins'=>'application/x-internet-signup','isp'=>'application/x-internet-signup',
			
			'jpg'=>array('image/jpeg','image/pjpeg'),
			'jpeg'=>array('image/jpeg','image/pjpeg'),
			'jfif'=>'image/pipeg','jpe'=>'image/jpeg',
			'js'=>'application/x-javascript',
			'json'=>'application/json',
	
			'latex'=>'application/x-latex','lha'=>'application/octet-stream','lsf'=>'video/x-la-asf','lsx'=>'video/x-la-asf',
			'lzh'=>'application/octet-stream',
			
			'm13'=>'application/x-msmediaview','m14'=>'application/x-msmediaview','m3u'=>'audio/x-mpegurl',
			'man'=>'application/x-troff-man','mdb'=>'application/x-msaccess','me'=>'application/x-troff-me',
			'mht'=>'message/rfc822','mhtml'=>'message/rfc822','mid'=>'audio/mid','mny'=>'application/x-msmoney',
			'mov'=>'video/quicktime','movie'=>'video/x-sgi-movie','mp2'=>'video/mpeg','mp3'=>'audio/mpeg',
			'mpa'=>'video/mpeg','mpe'=>'video/mpeg','mpeg'=>'video/mpeg','mpg'=>'video/mpeg',
			'mpp'=>'application/vnd.ms-project','mpv2'=>'video/mpeg','ms'=>'application/x-troff-ms','mvb'=>'application/x-msmediaview',
		
			'nws'=>'message/rfc822',
		
			'oda'=>'application/oda',
		
			'p10'=>'application/pkcs10','p12'=>'application/x-pkcs12',
			'p7b'=>'application/x-pkcs7-certificates','p7c'=>'application/x-pkcs7-mime','p7m'=>'application/x-pkcs7-mime',
			'p7r'=>'application/x-pkcs7-certreqresp','p7s'=>'application/x-pkcs7-signature','pbm'=>'image/x-portable-bitmap',
			'pdf'=>'application/pdf','pfx'=>'application/x-pkcs12','pgm'=>'image/x-portable-graymap',
			'pko'=>'application/ynd.ms-pkipko','pma'=>'application/x-perfmon','pmc'=>'application/x-perfmon',
			'pml'=>'application/x-perfmon','pmr'=>'application/x-perfmon','pmw'=>'application/x-perfmon',
			'pnm'=>'image/x-portable-anymap','pot'=>'application/vnd.ms-powerpoint',
			'ppm'=>'image/x-portable-pixmap','pps'=>'application/vnd.ms-powerpoint',
			'png'=>array('image/png','image/x-png'),
			'ppt'=>'application/vnd.ms-powerpoint','prf'=>'application/pics-rules','ps'=>'application/postscript',
			'pub'=>'application/x-mspublisher','pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		
			'qt'=>'video/quicktime',
		
			'ra'=>'audio/x-pn-realaudio','ram'=>'audio/x-pn-realaudio','ras'=>'image/x-cmu-raster','rgb'=>'image/x-rgb',
			'rmi'=>'audio/mid','roff'=>'application/x-troff','rtf'=>'application/rtf','rtx'=>'text/richtext',
			'scd'=>'application/x-msschedule','sct'=>'text/scriptlet','setpay'=>'application/set-payment-initiation',
			'setreg'=>'application/set-registration-initiation','sh'=>'application/x-sh','shar'=>'application/x-shar',
			'sit'=>'application/x-stuffit','snd'=>'audio/basic','spc'=>'application/x-pkcs7-certificates',
			'spl'=>'application/futuresplash','src'=>'application/x-wais-source','sst'=>'application/vnd.ms-pkicertstore',
			'stl'=>'application/vnd.ms-pkistl','stm'=>'text/html','svg'=>'image/svg+xml',
			'sv4cpio'=>'application/x-sv4cpio','sv4crc'=>'application/x-sv4crc','swf'=>'application/x-shockwave-flash',
			't'=>'application/x-troff','tar'=>'application/x-tar','tcl'=>'application/x-tcl',
			'tex'=>'application/x-tex','texi'=>'application/x-texinfo','texinfo'=>'application/x-texinfo',
			'tgz'=>'application/x-compressed','tif'=>'image/tiff','tiff'=>'image/tiff','tr'=>'application/x-troff',
			'trm'=>'application/x-msterminal','tsv'=>'text/tab-separated-values','txt'=>'text/plain',
		
			'uls'=>'text/iuls','ustar'=>'application/x-ustar',
		
			'vcf'=>'text/x-vcard','vrml'=>'x-world/x-vrml',
		
			'wav'=>'audio/x-wav','wcm'=>'application/vnd.ms-works','wdb'=>'application/vnd.ms-works',
			'wks'=>'application/vnd.ms-works','wmf'=>'application/x-msmetafile','wps'=>'application/vnd.ms-works',
			'wri'=>'application/x-mswrite','wrl'=>'x-world/x-vrml','wrz'=>'x-world/x-vrml',
		
			'xaf'=>'x-world/x-vrml','xbm'=>'image/x-xbitmap','xla'=>'application/vnd.ms-excel',
			'xlc'=>'application/vnd.ms-excel','xlm'=>'application/vnd.ms-excel',
			'xls'=>'application/vnd.ms-excel','xlt'=>'application/vnd.ms-excel','xlw'=>'application/vnd.ms-excel',
			'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xml'=>'text/xml',
			
			'xof'=>'x-world/x-vrml','xpm'=>'image/x-xpixmap','xwd'=>'image/x-xwindowdump',
			'z'=>'application/x-compress','zip'=>'application/zip',
			'*'=>'application/octet-stream',
					 
		);
	
	/**
	 * 将变量直接保存到PHP文件,便于引用
	 * @param string $varName 变量名称
	 * @param mixed $varData  变量数据
	 * @param string $file    保存的文件
	 * @param array $header 文件头信息 
	 */
	public static function saveVarsFile($varData,$file,$header=null){
		
		
		$export = var_export($varData,true);
		$const = "\nif(!defined('IN_WEB')){\n exit;\n}\n";
		
		/**加文件头信息**/
		if(validate::isNotEmpty($header,true)){
			
			$headerStr="/**\n";
			foreach($header as $k=>$h){
				
				$headerStr="@$k $h\n";
				
			}	
			
			$headerStr.="*/\n";
		}
		
		
		$code  = "<?php\n$const return $export ;\n\r?>";
		
		return self::writeFile($file,$code,LOCK_EX);
		
	}
	
	
	/**
	 * 创建时间文件夹
	 * @param string $dirname 文件夹名称
	 * @param string $parent 父目录
	 * @param string 返回创建后的目录路径
	 */
	public static function mkTimeDir($dirname,$parent){
		
		if(!is_dir($parent)){
			self::error($parent.'目录不存在！',1);
			return false;
		}
		
		$path = realpath($parent).'/'.date('Ymd').$dirname;
		if(!file_exists($path)){
			self::mkdir($path);
		}
		
		return $path;
		
	}
	
	/**
	 * 创建一个时间文件
	 * @param string $fileName 文件名称
	 * @param string $parent 存在路径
	 * @param string $pre 文件名前辍
	 */
	public static function mkTimeFile($fileName,$parent){
		
		if(!is_dir($parent)){
			self::error($parent.'目录不存在！',1);
			return false;
		}
		
		$path = realpath($parent).'/'.date('Ymd').$fileName;
		if(!file_exists($path)){
			self::writeFile($path,null);
		}
		
		return $path;
	}
	
	/**
	 * 获取一个地址的http访问地址
	 * @param string $dir
	 * @return string 
	 */
	public static function getVisitURL($dir){
		
		if(!file_exists($dir)){
			return false;
		}
		
		$path = realpath($dir);
		if(substr($path,0,strlen(WEB_ROOT))!=WEB_ROOT && realpath(WEB_ROOT)!=$path  ){
			return false;
		}
		
		$url = website::$url['host'].trim(substr($path,strlen(realpath(APP_PATH))),'\\');
		$url = str_replace('\\','/',$url);
		
		if(is_dir($path)){
			$url.='/';
		}
		
		return $url;
		
	}
	
	/**
	 * 创建一个目录，并执行相应的同步事件
	 * @param string $dir 目录
	 * @return boolean
	 */
	public static function mkdir($dir){
		
		if(!is_dir($dir)){
		    
			if(strstr($dir,'/')){
			    
			    $dirs = explode('/',$dir);
			    $path = '';
			    $res = true;
			    
			    foreach($dirs as $k=>$d){
			        
			        if($d!=''){
			        	$path.=$d.'/';
			        	$res = @mkdir($path);
			        }
			        
			    }
			    
			}else{
				$res = @mkdir($dir);
			}
			
			if($res  && self::$apiMode == false && arrayObj::getItem(website::$config,'file_async') == true){
				website::doEvent('file.mkdir',array(filer::relativePath($dir) ));
			}
			
			return $res;
		}
		
		return false;
	}
	

	
	
	/**
	 * 创建文件,使用file_put_contents函数写入
	 * @param string $filename 文件名，必须为绝对路径
	 * @param mixed $data 数据
	 * @param int $flags 写入类型 标型 FILE_APPEND | LOCK_EX等参数
	 * @param int  $context 
	 */
	public static function writeFile($filename,$data,$flags=null,$context=null){
		

		
		website::debugAdd('写入到文件'.$filename);
		$res = file_put_contents($filename,$data,$flags,$context);
		
		if($res && self::$apiMode == false && arrayObj::getItem(website::$config,'file_async') == true){
			website::doEvent('file.write',array(filer::relativePath($filename),$data,$flags,$context ));
		}
		
		return $res;
	}
	
	
	/**
	 * 按行来读取文件,并写到一个数组
	 * @param string $filename 文件名
	 * @param callback $callback 回调函数 func($line行代码,$data数据列表,$lineNum 行号)
	 * @return array
	 */
	public static function readFileLine($filename,$callback = null,$csv=false){
		
		if(!is_file($filename)){
			return array();
		}
		
		if(is_callable($callback)){
			
			$fb = fopen($filename,'r');
			$data = array();
			$lineNum = 1;
			while(!feof($fb)){
				
				$line = $csv ? fgetcsv($fb) : fgets($fb);
				if($csv){
					if($line!=null){
						$line = array_map(function($v) { return @iconv('gbk','utf-8//ignore',$v); },$line);
					}else{
						break;
					}
				}
				
				$data = call_user_func($callback,$line,$data,$lineNum);
				$lineNum++;
			}
			
			fclose($fb);
		}else{
			if($csv){
				while(!feof($fb)){
					$data[] = fgetcsv($fb);
				}
			}else{
				$data = file($filename);
			}
		}
		
		return $data;
		
	}
	
	
	/**
	 * 读取文件
	 * @param string $filename 文件名
	 * @param int $flags 读取
	 */
	public static function readFile($filename,$flags=null){
		
		if(!is_file($filename)){
			return false;
		}
		
		website::debugAdd('读取文件'.$filename);
		return file_get_contents($filename,$flags);
		
	}
	
	
	public static function deleteFile($file){
		if(!is_file($file)){
			return false;
		}
		
		$old = filer::relativePath($file);
		$res = @unlink($file);
		if($res && self::$apiMode == false && arrayObj::getItem(website::$config,'file_async') == true){
			website::doEvent('file.delete',array($old));
		}
		
		return $res;
	}
	
	/**
	 * 读取或发送远程请求的文件,使用curl扩展库
	 * @param string $url 请求地址
	 * @param string $method 请求方式get,post
	 * @param array $param 请求参数，一般在post时使用
	 */
	public static function doHttpReq($url,$method='get',$param=null,$wait=10){
		
		$timeout = $wait>0 ? $wait : 10 ; 
		$ch = curl_init();		
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_REFERER ,$url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true); 
		curl_setopt($ch,CURLOPT_USERAGENT,self::$userAgent);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
		
		if(self::$header!=null){
			curl_setopt($ch, CURLOPT_HTTPHEADER, self::$header);
		}
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		
		#curl_setopt($ch, CURLOPT_PROXY, "106.187.103.192");
		#curl_setopt($ch,CURLOPT_PROXYTYPE,CURLPROXY_SOCKS5);
		#curl_setopt($ch, CURLOPT_PROXYPORT, 1723);
		#curl_setopt($ch,CURLOPT_PROXYUSERPWD,'yhbhiron:522842');
		
		if($method =='post'){
			 curl_setopt($ch, CURLOPT_POST, 1);
		}
		
		if($param!=null){
			curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
		}
		
		if(self::$cookieFile!=''){
			curl_setopt($ch, CURLOPT_COOKIEJAR, self::$cookieFile);
			curl_setopt($ch, CURLOPT_COOKIEFILE, self::$cookieFile);
		}
		
		
		website::debugAdd('远程请求:'.$url.';方法：'.$method.';参数：'.var_export($param,true));
		$handles  = curl_exec($ch);
		self::$httpHeader = curl_getinfo($ch);
		
		curl_close($ch);
		return $handles;
		
	}
	
	public static function doHttpReqEx($url,$method='get',$param=null,$return=true){
		
		$info = parse_url($url);
		if(!isset($info['host'])){
			return;
		}

		
		$port = arrayObj::getItem($info,'port')!= '' ? $info['port'] : '80';
		$fp = @fsockopen($info['host'],$port,$errno,$errstr,10);

		if(!$fp){
			self::error($errno.$errstr,2);
			return;
		}
		
		$header = array();
		$method = strtoupper($method);
		$query  = isset($info['query']) ? "?{$info['query']}" : '';
		$path   = isset($info['path']) ? $info['path'] : '/';
		$header[] = "$method $path$query HTTP/1.1";
		$header[] = "Host: {$info['host']}";
		$header[] ='User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0';
		
		if($return){
			$header[] ='Connection: keep-alive';
		}else{
			$header[] ='Connection: Close';
		}
		
		$header[] ='Pragma: no-cache';
		$header[] = 'Cache-Control: no-cache';
		if($param!=null){
			
			$pstr = '';
			foreach($param as $k=>$p){
				$pstr.=$k.'='.urlencode($p);
			}
			
			$header[] = $pstr;
		}

		$header = implode("\r\n",$header);
		$header.="\r\n\r\n";
		fwrite($fp,$header);	
		
		$resp = null;
		if($return){
			$headerOver = false;
			$line = 1;
			while(!feof($fp)){
				
				$line =fgets($fp);
				if($headerOver == false){
					if($line == 1){
						preg_match('/HTTP\/.+\s*(\d+)\s*/i',$line,$m);
						if(arrayObj::get($m,1)!='200'){
							break;
						}
					}
					
				}
				$line++;
				
				if($headerOver){
					$resp.=$line;
				}		
						
				if($headerOver == false && trim($line) == ''){
					$headerOver = true;
				}

			}
		}
		
		fclose($fp);
		return $resp;
		
	}
	
	
	
	/**
	上传文件，支持数组文件上传和单个文件上传
	@param string $_file 文件字段名称
	@param string $savePath 保存文件的路径
	@param string $newName 保存文件的新名称
	@return array/string 上传后的路径地址或数组 array(old_name=>上传名称,save_path保存 后相对于app的路径,'abs_path'=>文件绝对路径)
	**/
	public static function upload($_file,$savePath,$newName=null){
		
		
		if(!is_dir($savePath)){
			website::debugAdd('上传目录不存在'.$savePath);
			return array();
		}
		
		if(isset($_FILES[$_file]) && $_FILES[$_file]!=null){
			
			/*上传文件数组*/
			if(is_array($_FILES[$_file]['name'])){
				
				$returnPath = array();
				foreach($_FILES[$_file]['tmp_name'] as $k=>$tmpname){
					
					if($_FILES[$_file]['size'][$k]>0 && is_uploaded_file($tmpname)){
						
						/**删除已上传的文件*/
						if(self::$uploadSizeLimit>0 && $_FILES[$_file]['size'][$k]>self::$uploadSizeLimit*1024*1024){
							
							if($returnPath!=null){
								foreach($returnPath as $n=>$up){
									@unlink($up['abs_path']);
								}
							}
							
							return array();
						}

						$ext  = strtolower(substr(strrchr($_FILES[$_file]['name'][$k],'.'),1));
						$mime = self::getMimeType($ext);
						
						if(self::$allowFormat == null || 
							(self::$allowFormat!=null && in_array($ext,self::$allowFormat) && self::isRightMine($_FILES[$_file]['type'][$k],$mime) )
						){
							
							if($newName == null){	
							
								$absFilePath = $savePath.'/'.StrObj::randStr(20,StrObj::RND_MIXED).'.'.$ext;
								move_uploaded_file($tmpname,$absFilePath);
								
							}else{
								
								$absFilePath = $savePath.'/'.$newName.$k.'.'.$ext;
								move_uploaded_file($tmpname,$absFilePath);
							}
							
							$filePath = self::relativePath($absFilePath,WEB_ROOT);
							if(self::$apiMode == false && $filePath!='' && arrayObj::getItem(website::$config,'file_async') == true){
								website::doEvent('file.write',array($filePath,file_get_contents($absFilePath)));
							}
							
							$returnPath[$k] = array('old_name'=>$_FILES[$_file]['name'][$k],'save_path'=>$filePath,'abs_path'=>realpath($absFilePath));
						}else{
							website::debugAdd('上传的文件格式不正确!');
						}
					}
				}
				
				return $returnPath;
				
			/*单个文件上传*/
			}else{
	
				if($_FILES[$_file]['size']>0 && is_uploaded_file($_FILES[$_file]['tmp_name'])){
					
					$ext = strtolower(substr(strrchr($_FILES[$_file]['name'],'.'),1));
					$mime = self::getMimeType($ext);
					if( self::$allowFormat == null || 
						( self::$allowFormat != null && in_array($ext,self::$allowFormat)  && self::isRightMine($_FILES[$_file]['type'],$mime))
					  ){
					  	
						if(self::$uploadSizeLimit>0 && $_FILES[$_file]['size']>self::$uploadSizeLimit*1024*1024){
							return array();
						}
											  	
						if($newName == null){
							
							$absFilePath = $savePath.'/'.StrObj::randStr(20,StrObj::RND_MIXED).'.'.$ext;
							move_uploaded_file($_FILES[$_file]['tmp_name'],$absFilePath);
		
						}else{
							$absFilePath = $savePath.'/'.$newName.'.'.$ext;
							move_uploaded_file($_FILES[$_file]['tmp_name'],$absFilePath);
							
						}
						
						$filePath = self::relativePath($absFilePath,WEB_ROOT);
						if(self::$apiMode == false && $filePath!='' && arrayObj::getItem(website::$config,'file_async') == true){
							website::doEvent('file.write',array($filePath,file_get_contents(WEB_ROOT.$filePath)));
						}
						
						return array('old_name'=>$_FILES[$_file]['name'],'save_path'=>$filePath,'abs_path'=>realpath($absFilePath));
					}else{
						website::debugAdd('上传文件:'.$ext.'格式不正确');
					}
				
				}else{
					website::debugAdd('上传文件错误');
				}
				
			}
			
		}
		
		return array();
		
	}
	
	/**
	 * 将文件上传到完程服务器
	 * @param string $file 本地文件或远程地址
	 * @param string $removeURL 上传的目标地址
	 * @param string $name 上传的file字段名称
	 */
	public static function uploadToRemote($file,$removeURL,$name){
		
		$isURL = validate::isURL($file);
		if($isURL){
			
			$data = self::doHttpReq($file);
			if($data == ''){
				return;
			}
			
			$fileName = WEB_ROOT.'/temp/'.uniqid();
			file_put_contents($fileName, $data);
			$header = self::$httpHeader;
			$mime   = $header['content_type'];
			
		}else{
			
			if(!is_file($file)){
				return;
			}
			
			$fileName = $file;
			$mime = self::getMimeType( arrayObj::getItem(pathinfo($file),'extension') );
			
		}
		
		$res = NULL;
		if(validate::isURL($removeURL)){
			
			$params = array(
				$name =>'@'.$fileName.';type='.$mime,
			);
			
			$res = self::doHttpReq($removeURL,'post',$params);
		}
		
		$isURL && @unlink($fileName);
		
		return $res;
	}
	
	
	/**
	 * 下载远程文件到服务器
	 * @param string $removeFile 远程地址
	 * @param string $savePath 保存的文件
	 * @param string $newName 设定新文件名，可选，不填时为随机数字名称
	 */
	public static function downloadFile($removeFile,$savePath,$newName=null){
		
		if(!is_dir($savePath)){
			return false;
		}
		
		
		$code = self::doHttpReq($removeFile);
		$mime = self::$httpHeader['content_type'];
		
		$pathinfo = pathinfo($removeFile);
		$ext = arrayObj::getItem($pathinfo, 'extension');
		
		if(!validate::isNotEmpty($ext)){
			$ext  = self::getMimeExt($mime);
		}
		
		$ext  = $ext!='' ?  '.'.$ext : $ext;
		$filename = $savePath.'/'.($newName!='' ? $newName : StrObj::randStr(13) ).$ext;
		self::writeFile($filename, $code);
			
		
		return self::relativePath($filename);
	}
	
	
	/**
	 * 获取某路径相对于某一目录的相对地址
	 * @param string $path 完整文件或目录路径
	 * @param string $parent 相对于的父路径，默认为APP_PATH
	 * @return string
	 */
	public static function relativePath($path,$parent=null){
	
		if(!file_exists($path)){
			return false;
		}
		
		if($parent == null){
			$parent = APP_PATH;
		}
		
		
		$path = substr($path,strlen(realpath($parent)));
		$path = trim(str_replace('\\','/',$path),'/');

		return $path;
	}	
	
	/**
	 * 获取某路径相对于域名的网站目录
	 * @param string $path 完整文件或目录路径
	 */
	public static function relativeHostPath($path){
	
		if(!file_exists($path)){
			return false;
		}
		
		
		$path = substr($path,strlen($_SERVER['DOCUMENT_ROOT']));
		$path = trim(str_replace('\\','/',$path),'/');

		return $path;
	}	
	
	/**
	 * 获取扩展名对应该mime类型
	 * @param string $ext 扩展名小写
	 * @return string mime类型字符
	 */
	public static function getMimeType($ext){
		
		$mimes = self::$mimes;
		$type = arrayObj::getItem($mimes,$ext);
		$type = $type==null ? 'application/octet-stream' : $type;
		unset($mimes);
		return $type;		
	}
	
	
	/**
	 * 获取mime类型对应的扩展名
	 * @param string $mime
	 */
	public static function getMimeExt($mime){
		
		$mimes = self::$mimes;
		foreach($mimes as $ext=>$m){
			if(self::isRightMine($mime, $m)){
				return $ext;
			}
		}
		
	}
	
	
	/**
	 * 获取文件扩展名
	 * @param string $file 文件名
	 * @return string
	 */
	public static function getFileExtension($file){
		return strtolower(arrayObj::getItem(pathinfo($file),'extension'));
	}
	
	
	
	/**
	 * 获断是否为正确的类型
	 * @param string $mime
	 * @param array $match
	 */
	private static function isRightMine($mime,$match){
		
		if($mime == null || !validate::isNotEmpty($match)){
			return false;
		}
		
		if(!is_array($match)){
			return $mime == $match;
		}else{
			return in_array($mime,$match);
		}
	}
	
	/**
	复制目录
	@param string $s 源目录
	@param string $d 目标目录
	**/
	public static function copyDir($s,$d){
		
		if(!is_dir($d) || !is_dir($s)){		
			return false;
		}
		
		$sub = scandir($s);
		foreach($sub as $k=>$v){
			if($v!='.' && $v!='..'){			
				$path = $s.'/'.$v;
				echo $path.website::$break;
				if(is_dir($path)){	
					
					!is_dir($d.'/'.$v) && mkdir($d.'/'.$v);
					self::copyDir($path,$d.'/'.$v);	
					
				}else{
					copy($path,$d.'/'.$v);
				}
			}
		}
		
		if( self::$apiMode == false && arrayObj::getItem(website::$config,'file_async') == true){
			website::doEvent('file.copydir',array(filer::relativePath($s),filer::relativePath($d) ));
		}
	}	
	
	
	
	/**
	 * 删除文件夹
	 * @param string $dir 被删除的目录
	 * @param boolean $delSelf 是否删除自己
	 */
	public static function deleteDir($dir,$delSelf=true,$issub=false){
		
		if(!is_dir($dir)){
			return false;
		}
		
		$sub = scandir($dir);
		foreach($sub as $k=>$d){
			
			if($d!='.' && $d!='..'){
				$path = $dir.'/'.$d;
				if(is_dir($path)){				
					self::deleteDir($path,$delSelf,true);					
				}else{
					unlink($path);
				}
			}
		}
		
		if($delSelf == false && $issub == true){
			@rmdir($dir);
		}
		
		if($delSelf == true){
			@rmdir($dir);
		}
		
		if($issub == false  && self::$apiMode == false && arrayObj::getItem(website::$config,'file_async') == true){
			website::doEvent('file.deldir',array(self::relativePath(realpath($dir),WEB_ROOT),$delSelf));
		}
	}	
	
	
	/**
	 * 移动一个文件到指定目标
	 * @param string $from 需要移动的文件
	 * @param string $des 目标目录或文件
	 * @return string 移动目录的绝对路径
	 */
	public static function move($from,$des){
		
		if(!is_file($from) || (!is_file($des) && !is_dir($des)) ){
			return false;
		}
		
		if(is_dir($des)){
			$des = $des.'/'.basename($from);
		}
		
		$res = true;
		$res = $res && @copy($from,$des);
		$res = $res && @unlink($from);
		
		if($res  && self::$apiMode == false && arrayObj::getItem(website::$config,'file_async') == true){
			website::doEvent('file.onmove',array(self::relativePath($from,WEB_ROOT),self::relativePath($des,WEB_ROOT)));
		}
		
		return realpath($des);
	}
	
	/**
	 * 复制一个文件到指定目标
	 * @param string $from 需要移动的文件
	 * @param string $des 目标目录或文件
	 * @return string 新文件的绝对路径
	 */
	public static function copy($from,$des){
		
		if(!is_file($from)){
			return false;
		}
		
		if(is_dir($des)){
			$des = $des.'/'.basename($from);
		}else{
		    
		    $desDir = dirname($des);
		    if(!is_dir($desDir)){
		        return false;
		    }
		}
		
		if(@copy($from,$des)  && self::$apiMode == false){
			website::doEvent('file.oncopy',array(self::relativePath($from,WEB_ROOT),self::relativePath($des,WEB_ROOT)));
		}
		return realpath($des);
	}	
	
	
	/**
	 * 遍历文件目录,并可以运用回调函数处理
	 * @param string $dir
	 * @param callback $callback($file)
	 */
	public static function scandir($dir,$callback=null){
		
		if(!is_dir($dir)){
			return false;
		}
		
		$sub = scandir($dir);
		foreach($sub as $k=>$d){
			
			if($d!='.' && $d!='..'){
				$path = $dir.'/'.$d;
				if(is_dir($path)){	
					if(is_callable($callback)){
						call_user_func($callback,$path,true);	
					}
					self::scandir($path,$callback);					
				}else{
					if(is_callable($callback)){
						call_user_func($callback,$path,false);	
					}
				}
			}
		}

		
	}
	
	/**
	 * 获取一个文件的文件名
	 * @param string $file 文件完整路径
	 */
	public static function getFileName($file){
		
		if(!is_file($file)){
			return false;
		}
		
		$info = pathinfo($file);
		return substr($info['basename'],0,-strlen($info['extension'])-1);
	}
	
	
	
	public static function isFullPath($file){
		
		return substr($file,0,strlen(WEB_ROOT) ) == WEB_ROOT;		
	}
	
	/**
	 * 获取一个相对地址的绝对地址
	 * @param string $url 要解析的相对地址
	 * @param string $locateURL 当前着陆地址
	 * @return string
	 */
	public static function getFullURL($url,$locateURL){
		
		if(validate::isURL($url) || !validate::isURL($locateURL)){
			return $url;
		}
 		
		if(preg_match('/^#/i',$url) || preg_match('/^javascript/i',$url)){
			return $url;
		}
		
		$Urlinfo = parse_url($locateURL);
		$info = explode('/',$url);
		$info2 = explode('/',arrayObj::getItem($Urlinfo,'path'));
		$afterQuery = substr($url,0,1) == '?';
			
		if(substr($url,0,1) == '/'){
			$host  = arrayObj::getItem($Urlinfo,'host');
			return $Urlinfo['scheme'].'://'.$host.( $afterQuery ? '' : '/').trim($url,'/');
		}
		
		$last = $info2[count($info2)-1];
		if(strrpos($last, '.') > 0){
			unset($info2[count($info2)-1]);
		}
	
		$info = array_reverse($info);
		if($info!=null){
			foreach($info as $k=>$f){
				
				if($f == '..' && count($info2)>3){
					array_pop($info2);
				}
			}
		}
			
		return $Urlinfo['scheme'].'://'.rtrim($Urlinfo['host'],'/').'/'.ltrim(implode('/',$info2),'/').( $afterQuery ? '' : '/') .ltrim($url,'./');			
	
	}
	
	
	/**
	 * 格式化路径
	 * @param string $file
	 */
	public static function realpath($file,$load=true){
		return str_replace(array('\\\\','\\','//'),'/',$load ? realpath($file) : $file);
	}
	
	
	/**
	 * 是否为自己的链接
	 * @param string $url 链接地址
	 * @return boolean true/false
	 */
	public static function isMineURL($url){
		
		$info = parse_url($url);
		$host = arrayObj::getItem($info, 'host');
		return $host == $_SERVER['HTTP_HOST'];
	}

	
	/**
	 * 文件或目录是否可写，解决is_writable问题
	 * @param string $file 文件
	 * @return boolean
	 */
	public static function isWritable($file){
		
		if(function_exists('is_writable') && ini_get('safe_mode') == false){
			clearstatcache();
			return is_writable($file);
		}
		
		clearstatcache();
		if(is_dir($file)){
			
			$tempFile = $file.'/'.uniqid();
			$fp = @fopen($tempFile,'wr');
			if(!$fp){
				return false;
			}
			
			fclose($fp);
			@unlink($tempFile);
			
		}else{
			
			if(is_file($file)){
				$fp = @fopen($file,'w+');
				if(!$fp){
					return false;
				}
			}else{
				return false;
			}
			
		}
		
		return true;
	}
	
	
	
	
	/**
	 * 显示文件操作消息
	 * @param string  $msg 错误消息
	 * @param int $level  错误级别
	 */
	protected static function error($msg,$level){
		website::error($msg,$level,1);
	}
	
}

?>