<?php !defined('IN_WEB') && exit('Access Deny!');
/**
 * 文档管理器,用于生成框架的文档
 * @name 文档管理器
 * @author yhb
 *
 */
class docIndex extends website{
	
	/**当前系统*/
	public static $_system;
	
	/** CHM hhp文件的名称 */
	protected static $hhpFile = 'document.hhp';
	
	/** 文档目录结构 */	
	protected static $indexList = array();
	
	
	/**
	 * 用指定模板显示在网页中的文档目录
	 * @param unknown_type $url
	 */
	public static function showIndex($url=''){
		
		$config = self::system('document')->loadConfig('doc');
		$file = $config['index_tpl'];
		
		$indexList = self::createIndex();
		$tpl = new template(true);
		$tpl->assign('url',$url);
		$tpl->assign('doc_index',$indexList);
		return $tpl->fetchFile($file); 
	}

	
	/**
	 * 生成一个文档树
	 * @return array
	 */
	public static function createIndex(){
		
		
		self::$indexList[] = array(
			'name'=>'系统内置类',
			'class_list'=>self::getClsNameByType('class'),
		);
		
		self::$indexList[] = array(
			'name'=>'系统内置助手类',
			'class_list'=>self::getClsNameByType('func'),
		);		
		
		self::$indexList[] = array(
		    'name'=>'API接口文档',
		    'class_list'=>self::getClsNameByType('app'),
		);	
		
		return self::$indexList;
		
		
	}
	
	/**
	 * 获取当前应用的api文档列表
	 */
	public static function getApiDocList(){
	    return self::getClsNameByType('app');
	}
	
	
	/**
	 * 编译生成整个文档为一个chm文件
	 */
	public static function compileChm(){
		
		$config = self::system('document')->loadConfig('doc');
		$chmPath = $config['chm_path'];
		
		self::createChmHhcFile();
		self::createChmHhkFile();
		self::createChmHhpFile();
		self::createChmHtmlFiles();
		
		return exec($chmPath.'hhc.exe '.$chmPath.self::$hhpFile,$return);
	}

	
	protected static function createChmHtmlFiles(){
		
		$config = self::system('document')->loadConfig('doc');
		$indexList = self::createIndex();
		$chmPath = $config['chm_path'];	
		
		if($indexList == null){
			return;
		}
		
		foreach($indexList as $k=>$block){
			
			if(arrayObj::getItem($block,'class_list') == null){
				continue;
			}
			
			foreach($block['class_list'] as $n=>$cls){
				
				if(!class_exists($cls)){
					$code = '文档不存在或无法加载';
				}else{
					$doc = new document($cls);
					$code = $doc->render();
				}
				
				file_put_contents($chmPath.'/htmls/'.$cls.'.html',$code);
			}
		}
		
	}
	
	
	
	protected static function createChmHhcFile(){
		
		$config = self::system('document')->loadConfig('doc');
		$indexList = self::createIndex();
		$file = $config['hhc_file_tpl'];
		$chmPath = $config['chm_path'];
		
		$tpl = new template(true);
		$tpl->assign('doc_index',$indexList);
		$code = StrObj::conv($tpl->fetchFile($file),'utf-8','gb2312');
		filer::writeFile($chmPath.'hiron_doc.hhc',$code);
	}
	
	protected static function createChmHhpFile(){
		
		$config = self::system('document')->loadConfig('doc');
		$indexList = self::createIndex();
		$file = $config['hhp_file_tpl'];
		$chmPath = $config['chm_path'];
		
		$tpl = new template(true);
		$tpl->assign('doc_index',$indexList);
		$code = $tpl->fetchFile($file);
		filer::writeFile($chmPath.self::$hhpFile,$code);
	}
	
	protected static function createChmHhkFile(){
		
		$config = self::system('document')->loadConfig('doc');
		$indexList = self::createIndex();
		$file = $config['hhk_file_tpl'];
		$chmPath = $config['chm_path'];
		
		$tpl = new template(true);
		$tpl->assign('doc_index',$indexList);
		$code = StrObj::conv($tpl->fetchFile($file),'utf-8','gb2312');
		filer::writeFile($chmPath.'hiron_doc.hhk',$code);
	}
	
	
	protected static function getClsNameByType($type){
		
		$files = self::$autoFiles;
		if(arrayObj::getItem($files,'class_files') == null){
			return array();
		}
		
		$typeDir = array(
			'func'=>WEB_FUNC_DIR,
			'class'=>WEB_CLASS_DIR,
		    'app'=>APP_PATH.'/apps/',
		);
		
		if(!isset($typeDir[$type])){
			return array();
		}
		
		$dir = $typeDir[$type];
		$list = array();
		
		$files = array_merge($files['class_files'],$files['app_files']);
		foreach($files as $k=>$file){
			
			if(preg_match('/\/([^\/]+?)\.'.$type.'\.php/i',$file['file'],$m) && !preg_match('/\.svn\/|\.git\//',$file['file'])){
				
				$parentName = substr(dirname($file['file']),strlen(realpath($dir)));
				$name = str_replace('/','_',$parentName.'_'.$m[1]);
				$name = StrObj::getClassName($name);
				if($type == 'app'){
				    $name.='App';    
				}
				
				$list[$name] = $name;
				
			}
			
		}
		
		ksort($list);
		
		return $list;		
	}
	
	

	
}
