<?php
import('@.Model.CommonModel');
class MenuModel extends CommonModel {
	public function CheckVerify() {
		return md5($_POST['verify']) == $_SESSION['verify'];
	}
}
?>