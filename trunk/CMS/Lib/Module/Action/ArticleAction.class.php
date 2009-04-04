<?php
class ArticleAction extends BaseAction {
	protected  function _initialize(){
		$DAO = D('Article');
		$hotArticles = $DAO->findall('','id,readCount,title','readCount desc',10);
		//dump($hotArticles);
		$this->assign("hotArticles",$hotArticles);


		parent::_initialize();
	}

	function index(){
		$Id = $_GET['Id'];

		$dao = D('Menu');
		$menuc	=	$dao->find('id='.$Id.' AND status>0');

		$DAO = D('Article');
		$articles=$DAO->findall('status>0 AND menuId='.$Id,'commentCount,readCount,id,uid,menuId,title,titlecolor,cTime,status','cTime desc');

		$this->assign('title',$menuc->mcnname.' - ');
		$this->assign("menuc",$menuc);
		$this->assign("articles",$articles);

		$this->display();
	}

	function show(){
		$this->getAttach();
		$this->getComment();
		$Id = $_GET['Id'];
		$menuc = $_GET['menuc'];

		$DAO = D('Article');
		$aContent = $DAO->find('status>0 AND id='.$Id);
		
		$dao = D('Menu');
		$menuc	=	$dao->find('id='.$aContent->menuId.' AND status>0');

		$this->assign('keywords',str_replace(' ',',',$aContent->tags));
		$this->assign('title',$aContent->title.' - ');
		$this->assign("aContent",$aContent);
		$this->assign("menuc",$menuc);

		$this->display();
	}

	function _after_show() {
		$Blog = D("Article");
		$blog = $this->get('aContent');
		// 阅读计数
		$id	=	$blog->id;
		if(!isset($_SESSION['blog_read_count_'.$id])) {
			$Blog->setInc('readCount',"id=".$id);
			$_SESSION['blog_read_count_'.$id]		=	true;
		}
	}

	/**
     +----------------------------------------------------------
     * 取得操作成功后要返回的URL地址
     * 默认返回当前模块的默认操作 
     * 可以在action控制器中重载
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	function getReturnUrl()
	{
		return __URL__.'?'.C('VAR_MODULE').'='.MODULE_NAME.'&'.C('VAR_ACTION').'='.C('DEFAULT_ACTION');
	}

}
?>