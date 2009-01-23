<?php 
import('@.Model.CommonModel');
class CommentModel extends CommonModel
{


	// 验证信息、自动填充信息、关联信息定义 
	protected $_auto	 =	 array(
		array('cTime','time','ADD','function'),	
		array('content','htmlspecialchars','ADD','function'),
		array('status','1','ADD'),		
		array('ip','get_client_ip','ADD','function'),
		array('agent','userAgent','ADD','callback'),
		);
	protected $_validate	 =	 array(
		array('author','require','用户名必需!'),
		array('email','require','邮箱必需'),
		array('email','email','邮箱格式错误',2),
		array('content','require','回复内容必需'),
		array('verify','require','验证码必需'),
		array('verify','CheckVerify','验证码错误',0,'callback'),
		);

	// 在下面添加需要的数据访问方法 


	public function userAgent() {
		return strval($_SERVER["HTTP_USER_AGENT"]);
	}
}
?>