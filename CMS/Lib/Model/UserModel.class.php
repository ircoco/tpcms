<?php
import('@.Model.CommonModel');
class UserModel extends CommonModel
{
	//表单验证
	protected  $_validate = array(
	    array('username','require','姓名不能为空！'),
	    array('email','require','E-MAIL不能为空！'),
	    array('password','require','密码必需！'),
	    array('verify','require','验证码必需！'),
		array('username','','用户名已经存在！',0,'unique','add'),
		array('email','email','邮箱格式不正确！',2),
		array('repassword','password','密码不一致！',0,'confirm'),
		array('verify','CheckVerify','验证码错误',0,'callback'),
	);

	//自动字段填充
	protected $_auto = array(
		array('registerTime','time','ADD','function'),
		array('lastLoginTime','time','ADD','function'),
	);
}
?>