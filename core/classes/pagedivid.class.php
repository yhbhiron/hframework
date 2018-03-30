<?php !defined('IN_WEB') && exit('Access Deny!');
/**
分页类,用于各类分页中,支持sql分页和数组分页
@author:jackborwn
@time:2016-6-2
@version:2.1.1
*/
class PageDivid extends Model{
	
	/**数据源类型,sql查询语句,array数据*/
	private $sourceType = 'array';
	
	/**数据源*/
	private $source ='';
	
	/**记录总数*/
	public $resCount = -1;
	
	/**保存页面大小**/
	private $pageSize = 8;
	
	/**保存页面数量**/
	private $pageSum = 0;
	
	/**分页基本拼接地址，如果是伪静或url得写，使用{page}字符代码第几页的变量*/
	public $baseURL = '';
	
	/**设置当前页面**/
	public $curPage = 1;
	
	/**当前可视范围的显示条数*/
	public $showNumSum = 10;
	
	/**链接中保存页码的参数，一般是get的参数*/
	public $pageName = 'page';
	
	/**分页相关参数,主要是用于非模板的情况或多个分页的情况下使用*/
	protected $pageParam = array();
	
	public $dbkey = '';

	
	/**
	 * @param mixed $s 数据源
	 * @param int $size 页面大小 
	 */
	public function __construct($s,$size=8){
		
		if((is_string($s) && $s=='') || $s==null){
			
			return null;
		}
		
		if(is_array($s)){
			$this->sourceType = 'array';	
		}else if($s instanceof  query){
			$this->sourceType = 'query';
		}else{
			throw new Exception('错误的数据源!');	
		}
		
		if(intval($size)<=0){
			$size = 8;
		}

		$this->source = $s;
		$this->pageSize =$size;
		$this->curPage = request::req($this->pageName);
		
	}
	
	/**
	 * 组建分页，并返回分页后的数据
	 * 获取分类的参数
	 * pageDivide->pageParam属性
	 * 
	 * @param boolean $link是否返回查询资源,默认false
	 * @return mixed 如果$link为true时刚返回查询的资源，false时返回数组
	 */
	public function buildPager($link=false){
		
		if($this->source==null){
			return false;
		}
		
		$this->pageParam['baseurl']	  = $this->baseURL;	
		
		$resSum  = $this->getResSum();
		$pageSum = $this->getPageSum();
		$curPage = $this->getCurPage();
		$resArea = $this->getResArea();
		$links   = $this->getPageLink();
		$numList = $this->getPageNumList();
		$resource = $this->getSource($curPage,$link);
		
		$this->pageParam['pagesum']  = $pageSum;
		$this->pageParam['nextpage'] = arrayObj::getItem($links,'next_page');
		$this->pageParam['prepage']  =arrayObj::getItem($links,'pre_page');
		$this->pageParam['size']     =$this->pageSize;
		$this->pageParam['pagelist'] =$numList;
		$this->pageParam['curpage']  =$curPage;
		$this->pageParam['firstpage'] = arrayObj::getItem($links,'first_page');
		$this->pageParam['lastpage']  = arrayObj::getItem($links,'last_page');
		$this->pageParam['recordsum'] =$resSum;	
		$this->pageParam['area'] = $resArea;
		
		return $resource;
		
	}
	
	
	/**
	 * @desc 获取分页的相关参数
	 * pagesum 分页总数
	 * nextpage下一页链接
	 * prepage 上一页链接
	 * size 页面大小
	 * pagelist 当前页码显示列表
	 * curpage 当前页数
	 * firstpage 第一页链接
	 * lastpage 最后一页链接
	 * recordsum 记录总数
	 * @return array
	 */
	public function getParams(){
		return $this->pageParam;
	}
	
	
	/**
	 * 获取数据源
	 * @param int $curPage 当前页
	 * @param boolean $link 是否只返回查询句柄
	 * @return mixed
	 */
	protected function getSource($curPage,$link=false){
		
		if($this->resCount<=0){
			return array();
		}
		
		$start = ($curPage-1)*$this->pageSize;
		if($this->sourceType == 'array'){
			$resource = $this->resCount >0 ? array_slice($this->source,$start,$this->pageSize): $this->resource;
		}else if($this->sourceType == 'query'){
			
			if($link == false){
				$resource = $this->resCount >0 ? $this->source->limit($this->pageSize,$start)->execute($this->dbkey) : array();
			}else{
				$resource = $this->resCount >0 ? db::instance($this->dbkey)->getResArray($this->source->limit($this->pageSize,$start)) : false;	
			}
		}
		
		return $resource;
	}


	/**
	获取记录总数
	*/
	protected function getResSum(){
		
		if($this->resCount>=0){
			return $this->resCount;
		}
		
		if($this->sourceType == 'query'){
			
			$temp = clone $this->source;
			$record = arrayObj::getItem($temp->select( array('s'=>'count(*)') )->orderby('')->execute($this->dbkey),0);
			$this->resCount = arrayObj::getItem($record, 's',0);
			
		}else if($this->sourceType == 'array'){
			$this->resCount = count($this->source);
		}
		
		return $this->resCount;
	}
	
	/**
	获取当前是第几页
	*/
	protected function getCurPage(){
		
		$this->curPage=intval($this->curPage)<=0 || $this->curPage=='' || empty($this->curPage) ? '1':intval($this->curPage);
		$this->curPage=$this->curPage > $this->pageSum && $this->pageSum>0 ? $this->pageSum : $this->curPage;
		$this->curPage=$this->curPage > $this->pageSum && $this->pageSum==0 ? 1 : $this->curPage;		
		
		return $this->curPage;
	}
	
	/**
	 * 获取分页的数字
	 */
	protected function getPageNumList(){
		
		
		$end_show   = ($this->showNumSum + $this->curPage) >= $this->pageSum ? $this->pageSum : $this->showNumSum + $this->curPage;
		$start_show = $this->curPage - $this->showNumSum <= 0 ? 1 : $this->curPage - $this->showNumSum;
		
		$number = array();
		$isVirtulURL = preg_match('/{page}/',$this->baseURL);
		for($i=$start_show;$i<=$end_show;$i++)
		{	
			if(validate::isNotEmpty($this->baseURL)){
				if($isVirtulURL){
					$number[$i] = str_replace('{page}',$i,$this->baseURL);
				}else{
					$number[$i] = $this->baseURL.$this->pageName."=".$i;
				}
			}else{
				$number[$i] = $i;
			}
		}
		
		return $number;
	}
	
	/**
	获取当前页的记录
	**/
	protected function getResArea(){
		
	    $number = array();
		if($this->resCount>0 && $this->resCount % $this->pageSize==0 || ($this->curPage-1)*$this->pageSize+1+$this->pageSize<=$this->resCount){
			$number['start'] = (($this->curPage-1)*$this->pageSize+1);
			$number['end'] = $this->pageSize*$this->curPage;
			if($this->pageSize == 1){
			    $number['end'] = 0;
			}
		}
		else if(($this->curPage-1)*$this->pageSize+1+$this->pageSize > $this->resCount)
		{
			if($this->resCount % $this->pageSize >1){
			    $number['start'] =  (($this->curPage-1)*$this->pageSize+1);
			    $number['end'] = $this->resCount; 
			}else{
			    $number['start'] = $this->resCount;
			    $number['end'] = 0;
			}
		}
		
		return $number;
	}
	
	/**
	 * 获取页面总数
	 */
	protected function getPageSum(){
		
		if($this->resCount % $this->pageSize==0)
		{
			$this->pageSum = $this->resCount/$this->pageSize;
		}
		else
		{
			$this->pageSum =(int)($this->resCount/$this->pageSize)+1;
		}
		
		return $this->pageSum;
	}
	
	/**
	获取分页链接
	*可以使用{page}变量做为地址的参数：如index-{page}.html,将会变成index-1.html等数字
	*/
	function getPageLink(){

		
		$links = array();
		if(!preg_match('/{page}/',$this->baseURL)){
			$this->baseURL = !preg_match('/\?(.+)/is',$this->baseURL) ? $this->baseURL.'?' : $this->baseURL.'&';
		}	
			
		$pageNumNext  = $this->pageSize*$this->curPage < $this->resCount ? $this->curPage+1 : 0;
		$pageNumPre   = $this->curPage > 1 ? $this->curPage-1 : 0;
		$pageNumFirst = $this->pageSum > 1 ? 1 : 0;
		$pageNumLast  = $this->pageSum > 1 ? $this->pageSum : 0;
		
		$this->pageParam['page_nextnum']  = $pageNumNext;
		$this->pageParam['page_prenum']   = $pageNumPre;
		$this->pageParam['page_firstnum'] = $pageNumFirst;
		$this->pageParam['page_lastnum']  = $pageNumLast;
		
		
		if(!preg_match('/{page}/',$this->baseURL)){
			
			$links['next_page']  = $pageNumNext>0 ? ($this->baseURL).$this->pageName."=".($pageNumNext) : "javascript:";
			$links['pre_page']   = $pageNumPre>0  ? ($this->baseURL).$this->pageName."=".($pageNumPre) : "javascript:";
			$links['first_page'] = $pageNumFirst>0 ? ($this->baseURL).$this->pageName."=1" : "javascript:";
			$links['last_page']  = $pageNumLast > 0 ? ($this->baseURL).$this->pageName."=".$pageNumLast : 'javascript:';
			
		}else{
			$links['next_page']  = $pageNumNext>0 ? str_replace('{page}',$pageNumNext,$this->baseURL) : "javascript:";
			$links['pre_page']   = $pageNumPre>0 ? str_replace('{page}',$pageNumPre,$this->baseURL) : "javascript:";
			$links['first_page'] = $pageNumFirst>0 ? str_replace('{page}',1,$this->baseURL) : "javascript:";
			$links['last_page']  = $pageNumLast>0 ? str_replace('{page}',$pageNumLast,$this->baseURL) : 'javascript:';
		}
		
		return $links;
	
	}
	
	
}

?>