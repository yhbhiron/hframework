<?php

/**
 * 
 * @author 文件缓存
 *
 */
class cacheFile extends superCache{
	
	
	/**缓存文件存放目录**/
	protected $config =  array();
	
	
	
	public function __construct($key,$config){
		
		parent::__construct($key, $config);
		$this->config['cache_dir'] = website::loadConfig('cache.fileCache',false);
		
		if(!is_dir(arrayObj::getItem($this->config,'cache_dir'))){
			$this->error('缓存目录不存在',2);
		}
		
		$this->config['cache_dir'] = $this->cacheDir();
	}
	
	public function write($data,$expire=0){
		
		if($data == null){
			return;
		}
		
		$key = $this->key;
		if($expire<=0){
			$expire = $this->config['default_expire'];
		}
		
		$fileData = array();
		$fileData['hiron_cache_expire_time'] = time()+$expire;
		$fileData['hiron_cache_data'] = $data;
		
		$file = 	$this->config['cache_dir'].'/'.$key.'.cache';
		$data = serialize($fileData);
		website::debugAdd('filecache写入缓存文件'.$key);
		
		return filer::writeFile($file,$data);
		
	}
	
	
	public function read(){
		
		
		$file = $this->config['cache_dir'].'/'.$this->key.'.cache';
		$data = @unserialize( filer::readFile($file) );
		
		if($data != null){
			
			if($data['hiron_cache_expire_time']<time()){
				$this->delete($key);
				return null;
			}
			
			if($this->isChanged()){
				
			}
							
		}else{
			return;
		}

		
		return $data['hiron_cache_data'];
	}
	
	
	public function delete(){
		
		$key = $this->key;
		$file = $this->config['cache_dir'].'/'.$key.'.cache';
		return @unlink($file);
	}	
	
	
	protected function cacheDir(){
		
		$dir = $this->config['cache_dir'].'/'.StrObj::left(md5($this->config['cache_dir']),2);
		if(!is_dir($dir)){
			@mkdir($dir);
		}
		
		return $dir;
		
	}
	
	public function isChanged(){
		
		
	}
	
		
}
?>