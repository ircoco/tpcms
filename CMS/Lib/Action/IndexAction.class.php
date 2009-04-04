<?php
class IndexAction extends BaseAction {
	public function index(){
		$DAO=D("Announce");
		$announce=$DAO->findAll('','*','cTime desc',10);
		//dump($announce);
		$this->assign("announce",$announce);

		$DAO=D("Pages");
		$inpages=$DAO->findall('type=0 AND status>0','*');
		$this->assign("inpages",$inpages);

		$dat=new Model();
		//$DAO = D('Article');

		$iarticles=$dat->table(C('DB_PREFIX').'Article as a,'.C('DB_PREFIX').'Menu as b')->findAll('a.status>0 AND b.id=a.menuId AND b.indexMenu=1','a.menuId,b.id,a.id,a.uid,a.title,a.titlecolor,a.cTime,a.status,b.indexMenu','cTime desc',100);
		//dump($iarticles);

		//$iarticles=$DAO->findall('status>0 AND (select indexMenu from'.C('DB_PREFIX').'menu)','id,uid,menuId,title,mTime',100);
		$this->assign("iarticles",$iarticles);

		$linksd = new LinksModel();
		$wlinks =  $linksd->findAll('lpic="" AND status=1 ORDER BY displayorder desc');
		$piclinks =  $linksd->findAll('lpic!="" AND status=1 ORDER BY displayorder desc');
		$this->assign('wlinks',$wlinks);
		$this->assign('piclinks',$piclinks);

		$dao = D("Tag");
		$intags  = $dao->findAll('','id,name,count,module','',10);
		$this->assign('intags',$intags);
		
		$dao = D('Attach');
		$picattach = $dao->findAll("type like 'image%'",'*','rand()','4');
		$this->assign('picattach',$picattach);

		$this->display();
	}
}
?>