<?php 
class ModuleAction extends BaseAction {

	var $model = 'Module';

	function insert()
	{
		$model	=	D($this->model);
		$nmae   =  $_REQUEST['modname'];
		if(!file_exists(CMSMODULE_PATH.'Action/'.$nmae.'Action.class.php')) {
			$this->error('未找到模块，请确认模块文件已上传至相应目录');
		}else{
			if(false === $model->create()) {
				$this->error($model->getError());
			}
			//保存当前数据对象
			$id = $model->add();
			if($id) { //保存成功
				//成功提示
				$this->success(L('_INSERT_SUCCESS_'));
			}else {
				//失败提示
				$this->error(L('_INSERT_FAIL_'));
			}
		}
	}

	function update() {
		$model	=	D($this->model);
		$id         = $_REQUEST['id'];
		$nmae   =  $_REQUEST['modname'];
		if(!file_exists(CMSMODULE_PATH.'Action/'.$nmae.'Action.class.php')) {
			$this->error('未找到模块，请确认模块文件已上传至相应目录');
		}else{
			if(false === $vo = $model->create()) {
				$this->error($model->getError());
			}
			$result	=	$model->save($vo,"id=$id");
			if($result) {
				//成功提示
				$this->success(L('_UPDATE_SUCCESS_'));
			}else {
				//错误提示
				$this->error(L('_UPDATE_FAIL_'));
			}
		}
	}
}
?>