<?php
/**
图片操作类
@author: Jack Brown
@copyright: from online
@since 2013-12-27
 */
class ImgCtrol{
	const SAVE_PNG = 0;
	const SAVE_JPG = 1;
	const SAVE_GIF = 2;

	const MODE_CUT = 0;
	const MODE_SCALE = 1;

	const TYPE_GIF = 1;
	const TYPE_JPG = 2;
	const TYPE_PNG = 3;

	const SAVE_QUALITY = 100;

	static public function getImageType($fileName=null){
		$sizeInfo = getImageSize($fileName);
		return $sizeInfo[2];
	}

	static public function saveFile($image=null,$destFile=null,$saveType=self::SAVE_JPG){
		switch( $saveType ) {
			case self::SAVE_GIF: 
				return @imageGif($image, $destFile);
			case self::SAVE_JPG:
				return @imageJpeg($image, $destFile, self::SAVE_QUALITY);
			case self::SAVE_PNG:
				return @imagePng($image, $destFile);
				
			default:
				return false;
		}
	}
	
	
	/**
	 * 重定义图片大小
	 * @param string $srcFile 源图片路径
	 * @param string $destFile 另存为的图片路径
	 * @param int $width 新宽度
	 * @param int $height　新高度
	 * @param int $mode MODE_CUT剪切模式,MODE_SCALE等比缩放
	 */
	static public function resizeTo($srcFile=null,$destFile=null,$width=0,$height=0,$mode=self::MODE_SCALE){
		if(false === file_exists($srcFile) ){
			return false;
		}
		
		if(!validate::isNotEmpty($destFile)){
			$destFile = $srcFile;
		}

		preg_match( '/\.([^\.]+)$/', $destFile, $matches );
		switch( strtolower($matches[1]) )
		{
			case 'jpg':
				$saveType = self::SAVE_JPG;
				break;
			case 'jpeg':
				$saveType = self::SAVE_JPG;
				break;
			case 'gif':
				$saveType = self::SAVE_GIF;
				break;
			case 'png':
				$saveType = self::SAVE_PNG;
				break;
			default:
				$saveType = self::SAVE_JPG;
		}
		
		$type = self::getImageType($srcFile);
		$srcImage = null;
		switch ($type){
			case self::TYPE_GIF:
				$srcImage = imageCreateFromGif($srcFile);
				break;
			case self::TYPE_JPG:
				$srcImage = imageCreateFromJpeg($srcFile);
				break;
			case self::TYPE_PNG:
				$srcImage = imageCreateFromPng($srcFile);
				break;
			default:
				return false;
		}
		
		$srcWidth = imageSX($srcImage);
		$srcHeight = imageSY($srcImage);

		if($width==0 && $height==0){
			$width = $srcWidth;
			$height = $srcHeight;
			$mode = self::MODE_SCALE;
		}else if($width>0 & $height==0){
			$useWidth = true;
			$mode = self::MODE_SCALE;
			if ( $srcWidth <= $width ) {
				return self::saveFile($srcImage, $destFile, $saveType);
			}
		}else if($width==0 && $height>0){
			$mode = self::MODE_SCALE;
		}
		
		if( $mode == self::MODE_SCALE){
			if($width>0 & $height>0){
				$useWidth = (($srcWidth*$height) > ($srcHeight*$width)) ? true:false;
			}
			if( isset($useWidth) && $useWidth==true ){
				$height = ($srcHeight*$width)/$srcWidth;
			}else{
				$width = ($srcWidth*$height)/$srcHeight;
			}
		}
		
		$destImage = imageCreateTrueColor($width, $height);
		$alpha = imagecolorallocatealpha($destImage, 0, 0, 0, 127);  
		imagefill($destImage, 0, 0, $alpha); 		
		
		if( $mode==self::MODE_CUT ){
			
			
			if($srcWidth<$width && $srcHeight<$height){
				
				return @copy($srcFile,$destFile);
			}else{
				
				$useWidth = (($srcWidth*$height) > ($srcHeight*$width)) ? false : true; 
				
				if( $useWidth==true ){
					$tempWidth = $width;
					$tempHeight = ($srcHeight*$tempWidth)/$srcWidth;
				}else{
					$tempHeight = $height;
					$tempWidth = ($srcWidth*$tempHeight)/$srcHeight;
				}
	
				$tempImage = imageCreateTrueColor( $tempWidth, $tempHeight);
				$srcImage = imageCopyResampled( $tempImage, $srcImage,0,0,0,0,$tempWidth,$tempHeight,$srcWidth,$srcHeight);
				imageDestroy($srcImage);
				$srcImage = $tempImage;
				$srcWidth = $width;
				$srcHeight = $srcWidth*$width/$srcHeight;
			}
				
		}
		
		
		if( $mode == self::MODE_SCALE ){
			imageCopyResampled( $destImage, $srcImage, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight );
		}else{
			imageCopyResampled( $destImage, $srcImage, 0, 0, 0, 0, $width, $height, $width, $height );
		}
		
		imagesavealpha($destImage,true);
		
		@imageDestroy($srcImage);

		return self::saveFile($destImage,$destFile,$saveType);
	}
	
	
	/**
	 * 给指定图片文件加个水印
	 * @param string $file 图片文件路径
	 * @param string $wFile 水印文件路径,默认　/images/water.gif
	 */
	public static  function addWaterMark($file,$wFile=''){
		
		if(!file_exists($file)){
			return false;	
		}
		
		$type = self::getImageType($file);
		$srcImage = null;
		switch ($type){
			case self::TYPE_GIF:
				$srcImage = imageCreateFromGif($file);
				break;
			case self::TYPE_JPG:
				$srcImage = imageCreateFromJpeg($file);
				break;
			case self::TYPE_PNG:
				$srcImage = imageCreateFromPng($file);
				break;
			default:
				return false;
		}	
		
		$saveType = $type;
		
		
		if($wFile == '' || !file_exists($wFile)){
			
			$wFile = ROOT.'./images/water.gif';
		}
		
		$wtype = self::getImageType($wFile);		
		switch ($wtype){
			case self::TYPE_GIF:
				$wImage = imageCreateFromGif($wFile);
				break;
			case self::TYPE_JPG:
				$wImage = imageCreateFromJpeg($wFile);
				break;
			case self::TYPE_PNG:
				$wImage = imageCreateFromPng($wFile);
				break;
			default:
				return false;
		}	
		
		
		$srcWidth  = imageSX($srcImage);
		$srcHeight = imageSY($srcImage);
		
		$waWidth  = imageSX($wImage);
		$waHeight = imageSY($wImage);
		
		$posX = $srcWidth - 4 - $waWidth;
		$posY = $srcHeight - 4 - $waHeight;
		
		if($posX<0 || $posY<0){
			
			@imagedestroy($srcImage);
			@imagedestroy($wImage);
			return false;	
		}
		
		imagecopy($srcImage,$wImage,$posX,$posY,0,0,$waWidth,$waHeight);
		self::saveFile($srcImage,$file,$saveType);
		
		@imagedestroy($srcImage);
		@imagedestroy($wImage);
		
	}
	
	
	/**
	 * 验证码生成
	 * @param string $name session名称
	 * @param int $type 生成类型 1 数字 2大写字母 3加法运算,默认1
	 * @param int $w 宽度,默认100
	 * @param int $h 高度,默认30
	 */
	public static function randCode($name,$type=1,$w=100,$h=30,$expire=30){
		
		httpd::setMimeType('jpg');
		$im    = imagecreate($w,$h);
		$rgb   = array('r'=>rand(128,255),'g'=>rand(128,255),'b'=>rand(128,255));
		$rgb2  = array('r'=>255-$rgb['r'],'g'=>255-$rgb['g'],'b'=>255-$rgb['b']);
		
		$color = imagecolorallocate($im,$rgb['r'],$rgb['g'],$rgb['b']);
		$hasRnd = true;
		$str = '';
		if($type == 1){
			$str   = StrObj::randStr(4,StrObj::RND_NUM);
			session::set($name,$str,$expire,true);
			$size = $h-5;
			$strLen = strlen($str)-1;
		}else if($type == 2){
			$str   = StrObj::randStr(4,StrObj::RND_UPPER);
			session::set($name,$str,$expire,true);
			$size = $h-5;
			$strLen = strlen($str)-1;
		}else if($type == 3){
			
			$num1 = rand(1,99);
			$num2 = rand(1,99);
			$str = $num1.'+'.$num2.'=?';
			session::set($name,$num1+$num2,$expire,true);
			$hasRnd = false;
			$strLen = strlen($str)-1;
			$size = $w/($strLen+1);
		}
		
		
		
		$fontPath = WEB_ROOT.'data/font/simkai.ttf';
		for($i=0;$i<=$strLen;$i++){
			$fontColor = imagecolorallocate($im,$rgb2['r'],$rgb2['g'],$rgb2['b']);
			if($hasRnd){
				imagettftext($im,$size,rand(0,90),15+$i*$size,$size+4,$fontColor,$fontPath,$str{$i});
			}else{
				imagettftext($im,$size,0,$i*$size,$size+4,$fontColor,$fontPath,$str{$i});
			}
		}
		
		$pointNum = 50;
		$pointColor = imagecolorallocate($im,125,125,125);
		for($i=0;$i<=$pointNum;$i++){
			imagesetpixel($im,rand(5,$w),rand(5,$h),$pointColor);
		}
		
		imagejpeg($im);
		imagedestroy($im);
	}
}

?>
