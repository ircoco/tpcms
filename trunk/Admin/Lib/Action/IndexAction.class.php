<?php 
// 后台管理
class IndexAction extends BaseAction{
	function _initialize()
	{
		if(!isset($_SESSION[C('USER_AUTH_KEY')])) {
			$this->redirect('login','Public');
		}
		parent::_initialize();
	}
	function index()
    {
		$this->display();
    }
}
?>