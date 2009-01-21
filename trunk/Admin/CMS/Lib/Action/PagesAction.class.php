<?php
class PagesAction extends BaseAction {
	public function index(){
		$Id =	$_GET['Id'];

		$dao = D('Pages');
		$pagec	=	$dao->find('pagesId='.$Id.' AND status>0');
		$this->assign("pagec",$pagec);

		$this->display();
	}
}
?>