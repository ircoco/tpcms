<?php 
class PublicAction extends BaseAction{

	public function checkLogin() {
		$secure_code	=	C('SECURE_CODE');
		$userUsername	=	$_POST["username"];
		$userPassword	=	md5($secure_code.md5($_POST["password"]));
		
		if(empty($_POST['username'])) {
			$this->error('帐号错误！');
		}elseif (empty($_POST['password'])){
			$this->error('密码必须！');
		}elseif ($_SESSION['verify'] != md5($_POST['verify'])){
			$this->error('验证码错误！');
		}
		
		//生成认证条件
        $map            =   array();
        $map["username"]	=	$_POST['username'];
        $map["status"]	=	array('gt',0);

		$userDao	=	D('User');
		$user	=	$userDao->find($map);

		//使用用户名、密码和状态的方式进行认证
		if(false === $user) {
			$this->error('用户名不存在或已禁用！');
		}else {
			if($user->username	!=  $_POST['username']) {
				$this->error('帐号错误！');
			}
			if(md5($secure_code.$user->password) != $userPassword) {
				$this->error('密码错误！');
			}}
			$_SESSION[C('USER_AUTH_KEY')]	=	$user->uid;
			$userDao->setField('lastLoginTime',time(),"uid=".$user->uid);
		    if($user->type =='a') {
				// 管理员不受权限控制影响
            	$_SESSION['administrator']		=	true;
            	$_SESSION['isAdmin']   =   true;
            }else{
				$_SESSION['administrator']		=	false;
			}
			/*记录登陆状态
			Session::set('uid',$user->uid);
			Session::set('userInfo',$user);
			Cookie::set('username',$userUsername,36000000);*/
			RBAC::saveAccessList();
			$this->success('登录成功！');
	}
	
	public function login() {
		if(!isset($_SESSION[C('USER_AUTH_KEY')])) {
			$this->display();
		}else{
			$this->redirect('index','Index');
		}
	}

	public function logout()
	{
		if(isset($_SESSION[C('USER_AUTH_KEY')])) {
			unset($_SESSION[C('USER_AUTH_KEY')]);
			unset($_SESSION['administrator']);
			unset($_SESSION['userInfo']);
			unset($_SESSION['isAdmin']);
			$this->assign("message",'登出成功！');
			$this->assign("jumpUrl",__URL__.'/login/');
		}else {
			$this->assign('error', '已经登出！');
		}
		$this->forward();
	}

	/**
     +----------------------------------------------------------
     * 验证码显示
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
	function verify()
	{
		import("ORG.Util.Image");
		if(isset($_REQUEST['adv'])) {
			Image::showAdvVerify();
		}else {
			Image::buildImageVerify();
		}
	}

	public function index()
	{
		//如果通过认证跳转到首页
		redirect(__APP__);
	}

	/**
     +----------------------------------------------------------
     * 取得操作成功后要返回的URL地址
     * 默认返回当前模块的默认操作 
     * 可以在action控制器中重载
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	function getReturnUrl()
	{
		return __URL__.'?'.C('VAR_MODULE').'='.MODULE_NAME.'&'.C('VAR_ACTION').'='.C('DEFAULT_ACTION');
	}

}
?>