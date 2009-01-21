<?php
class TagsAction extends BaseAction {

	function index(){
		$this->redirect('show');
	}

	function show(){
		$dao = D("Tag");
		$module=$_GET['module'];
		if(!empty($_GET['name'])) {
			$bfname=rawurldecode(trim($_GET['name']));
			/*$cha=mb_detect_encoding($bfname);
			if ($cha!="UTF-8"){
				$name=iconv($cha,"UTF-8",$bfname);
			}else{
				$name=$bfname;
			}*/
			$name = safeEncoding($bfname);
			//echo $name;
			if (!empty($module)){
				$list  = $dao->getFields("id,id","module='$module' and name='$name'");
			}else{
				$list  = $dao->getFields("id,id","name='$name'");
			}
			$tagId  =  implode(',',$list);
			$dao = D("Tagged");
			import("ORG.Util.Page");
			$listRows = 45;
			$fields	=	'a.aid,a.menuId,a.cTime,a.readCount,a.commentCount,a.title,c.moduleId,c.mcnname as menu';
			$count  =  $dao->count("tagId  in ('$tagId')");
			$p          = new Page($count,$listRows);
			$p->setConfig('header' ,'篇日志 ');
			$dao = D("Article");
			$list     = $dao->query("select ".$fields." from ".C('DB_PREFIX').'Article as a,'.C('DB_PREFIX').'tagged as b, '.C('DB_PREFIX').'menu as c where b.tagId  in ('.$tagId.') and a.menuId= c.menuId and a.status=1  and a.aid=b.recordId order by a.aid desc limit '.$p->firstRow.','.$p->listRows);
			if($list) {
				//分页显示
				$page       = $p->show();
				//模板赋值显示
				$this->assign("page",       $page);
				$this->assign('list',$list);
			}
			$this->assign('tag',$name);
			$this->assign("count",$count);
		}else {
			if (!empty($module)){
				$list  = $dao->findAll("module='$module'",'id,name,count,module');
			}else{
				$list  = $dao->findAll("",'id,name,count,module');
			}

			$this->assign('tags',$list);
		}
		$this->assign('tmodule',$module);
		$this->display();
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