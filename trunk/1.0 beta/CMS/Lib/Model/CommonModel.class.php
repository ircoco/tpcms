<?php 
class CommonModel extends Model
{//类定义开始

	public function CheckVerify($verify) {
		if(md5($verify) != Session::get('verify')) {
			return false;
		}
		return true;
	}
	
}//类定义结束
?>