<?php 
class ArticleAction extends BaseAction {

	var $model = 'Article';

	function insert()
	{
		$model	=	D($this->model);
		$vo = $model->create();
		if(false === $vo) {
			$this->error($model->getError());
		}
		//保存当前数据对象
		$id = $model->add($vo);
		if($id) { //保存成功
			if(is_array($vo)) {
				$vo[$model->getPk()]  =  $id;
			}else{
				$vo->{$model->getPk()}  =  $id;
			}
			if(method_exists($this,'_trigger')) {
				$this->_trigger($vo);
			}
			
			//成功提示
			$this->success(L('_INSERT_SUCCESS_'));
		}else {
			//失败提示
			$this->error(L('_INSERT_FAIL_'));
		}
	}

	function add() {
		$dao = D('Menu');
		$addMenu	=	$dao->findAll('','id,mcnname');
		$this->assign("addMenu",$addMenu);
		$this->assign("uid",$_SESSION[C('USER_AUTH_KEY')]);
		$this->display();
	}

	function edit() {
		$dao = D('Menu');
		$addMenu	=	$dao->findAll('','id,mcnname');
		$this->assign("addMenu",$addMenu);
		$model	=	D($this->model);
		$id     = $_REQUEST['id'];
		$vo	=	$model->find($id);
		$this->getAttach();
		//dump($vo);
		$this->assign('vo',$vo);
		$this->display();


	}

	function update() {
		$model	=	D($this->model);
		$id         = $_REQUEST['id'];
		if(false === $vo = $model->create()) {
			$this->error($model->getError());
		}
		$result	=	$model->save($vo,"id=$id");
		if($result) {
			$vo	=	$model->getById($id);
			//数据保存触发器
			if(method_exists($this,'_trigger')) {
				$this->_trigger($vo);
			}
			if(!empty($_FILES)) {//如果有文件上传
				//执行默认上传操作
				//保存附件信息到数据库
				$this->_upload(MODULE_NAME,$id);
			}
			$this->success(L('_UPDATE_SUCCESS_'));
		}else {
			//错误提示
			$this->error(L('_UPDATE_FAIL_'));
		}
	}
}
?>