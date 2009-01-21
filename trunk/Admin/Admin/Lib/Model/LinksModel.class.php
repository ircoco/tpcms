<?php
import('@.Model.CommonModel');
class LinksModel extends CommonModel {
	public function CheckVerify() {
		return md5($_POST['verify']) == $_SESSION['verify'];
	}
}
?>