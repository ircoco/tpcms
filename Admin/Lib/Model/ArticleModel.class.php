<?php
import('@.Model.CommonModel');
class ArticleModel extends CommonModel {
	public function CheckVerify() {
		return md5($_POST['verify']) == $_SESSION['verify'];
	}
	
	protected  $_validate = array(
	    array('title','require','标题不能为空！'),
	    array('aContent','require','内容不能为空！'),
	);
}
?>