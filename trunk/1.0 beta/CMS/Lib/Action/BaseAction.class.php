<?php
class BaseAction extends Action {

	var $uid;

	protected  function _initialize(){

		$this->uid	=	Session::get("uid");
		$this->userInfo	=	Session::get("userInfo");

		$fields	=	'*,a.modname as module';
		$dao = D("Menu");
		$menu     = $dao->query("select ".$fields." from ".C('DB_PREFIX').'Module as a,'.C('DB_PREFIX').'Menu as b where b.moduleId = a.mid and a.status>0 and b.status>0 and a.type=1 order by orderId asc');

		/*$dao = D('Menu');
		$menu	=	$dao->findAll('status>0','*','orderId desc');*/
		$this->assign("menu",$menu);

		$dao = D('Pages');
		$pages	=	$dao->findAll('status>0','pagesId,pname,pcnname,orderId,status','orderId desc');
		$this->assign("pages",$pages);
		
		$this->assign('uid',$this->uid);
		$this->assign('userInfo',$this->userInfo);
	}

	public function download()
	{
		import("ORG.Net.Http");
		$id         =   $_GET['id'];
		$dao        =   D("Attach");
		$attach	    =   $dao->getById($id);
		$filename   =   $attach->savepath.$attach->savename;
		if(is_file($filename)) {
			if(!isset($_SESSION['attach_down_count_'.$id])) {
				// 下载计数
				$dao->setInc('downCount',"attid=".$id);
				$_SESSION['attach_down_count_'.$id]	=	true;
			}
			Http::download($filename,auto_charset($attach->name,'utf-8','gbk'));
		}
	}

	public function getComment() {
		$id	=	$_GET['Id'];
		import("ORG.Util.Page");
		$Comment = D('Comment');
		$count	=	$Comment->count("recordId='$id'");
		$p          = new Page($count);
		$p->setConfig('header' ,'条评论');
		$comments = $Comment->findAll("module='".MODULE_NAME."' AND recordId='$id'",'acid,content,author,email,recordId,cTime','acid asc',$p->firstRow.','.$p->listRows);
		if($count>0) {
			//模板变量赋值
			$this->assign("comments",$comments);
			$page  = $p->show();
			//dump($comments);
			$this->assign("page",$page);
		}
	}

	// 发表评论
	public function comment()
	{
		// 创建评论对象
		$dao = D("Comment");
		$vo  =  $dao->create();
		if(!$vo) {
			$this->error($dao->getError());
		}
		if(!Session::get("uid")){
			$dao->author='<font color="Gray">[游客]</font>'.$_POST['author'];
		}
		// 保存评论
		$result  =  $dao->add();
		if($result) {
			// 更新评论数
			$objDao = D($vo->module);
			$objDao->setInc('commentCount',"aid='".$vo->recordId."'");
			// 返回客户端数据
			$vo->cTime  =  toDate($vo->cTime,'Y-m-d H:i:s');
			$vo->content = nl2br(ubb(trim($vo->content)));
			$vo->acid	 =	 $result;
			$this->ajaxReturn($vo,'评论发表成功');
		}else {
			$this->ajaxReturn($vo,$dao->getError().'评论失败！',0);
		}
	}

	// 删除评论
	public function delComment()
	{
		//删除指定记录
		$dao        = D("Comment");
		$id         = $_REQUEST['id'];
		if(isset($id)) {
			$comment   =  $dao->getById($id);
			if(!$comment) {
				$this->error('评论不存在！');
			}
			if($dao->deleteById($id)){
				// 更新日志评论数
				$dao = D($comment->module);
				$dao->setDec('commentCount',"aid=".$comment->recordId);
				$this->ajaxReturn($id,"评论删除成功",1);
			}else {
				$this->error('操作失败');
			}
		}else {
			$this->error('非法操作');
		}
	}

	public function getAttach() {
		//读取附件信息
		$id	=	$_GET['Id'];
		$dao = D('Attach');
		$attachs = $dao->findAll("module='".MODULE_NAME."' and recordId='$id'");
		if(count($attachs)>0) {
			//模板变量赋值
			$this->assign("attachs",$attachs);
		}
	}

	public function verify()
	{
		$type	 =	 isset($_GET['type'])?$_GET['type']:'gif';
		import("ORG.Util.Image");
		Image::buildImageVerify(4,1,$type);
	}

	function __destruct() {
	}
}//类定义结束
?>