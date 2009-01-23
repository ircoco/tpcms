<?php
import('@.Model.CommonModel');
class UserModel extends CommonModel
{
	//表单验证
	protected  $_validate = array(
		array('usernmae','','用户名已经存在',0,'unique','add'),
		array('name','require','姓名不能为空！'),
		array('password','require','密码必须！'),
		array('password2','password','密码不一致！',0,'confirm'),
	);

	//自动字段填充
	protected $_auto = array(
		array('registerTime','time','ADD','function'),
		array('lastLoginTime','time','ADD','function'),
	);

	//vip用户，特权
	function vip($ids,$status='3') {
		return $this->setStatus($ids,$status);
	}

	//通过验证的真实用户
	function t($ids,$status='2') {
		return $this->setStatus($ids,$status);
	}

	//未通过验证的用户，信息提醒
	function f($ids,$status='1') {
		return $this->setStatus($ids,$status);
	}

	//被锁定用户，限制行为
	function lock($ids,$status='0') {
		return $this->setStatus($ids,$status);
	}

	//设定用户状态
	function setStatus($ids,$status='2') {
		$result = $this->setField('status',$status,"id in ($ids)");
		if(false===$result){
			return false;
		}else{
			return true;
		}
	}
}
?>