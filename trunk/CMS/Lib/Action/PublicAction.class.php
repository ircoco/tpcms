<?php
class PublicAction extends BaseAction {
	public function index(){
		$this->redirect('checklogin');
	}

	public function login(){
		$this->assign("title",'登录 - ');
		$this->display();
	}

	//登录检测
	public function checklogin(){
		$secure_code	=	C('SECURE_CODE');
		$userUsername	=	$_POST["username"];
		$userPassword	=	md5($secure_code.md5($_POST["password"]));

		if(empty($_POST['username'])) {
			$this->error('帐号错误！');
		}elseif(empty($_POST['password'])){
			$this->error('密码必须！');
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
			}
		}
		$_SESSION[C('USER_AUTH_KEY')]	=	$user->uid;
		$userDao->setField('lastLoginTime',time(),"uid=".$user->uid);
		if($user->type =='a') {
			// 管理员不受权限控制影响
			$_SESSION['administrator']		=	true;
			$_SESSION['isAdmin']   =   true;
		}else{
			$_SESSION['administrator']		=	false;
		}
		Session::set('userInfo',$user);
		/*记录登陆状态
		Session::set('uid',$user->uid);
		Session::set('userInfo',$user);
		Cookie::set('username',$userUsername,36000000);*/
		RBAC::saveAccessList();
		$this->success('登录成功！');
		//$this->redirect("index","Index");
	}

	//注册
	public function reg(){
		if(!C('ALLOW_REG')){
			$this->assign("error",'注册关闭');
			$this->forward();
		}else{
			if(C('PASSPORT')){
				header('location:'.__APP__.'/Passport/'.C('PASSPORT_TYPE').'/reg/');
				//$this->redirect('pwreg','Passport');
			}
			$this->assign("title",'注册 - ');
			$this->display();
		}
	}

	public function doreg(){
		if(strlen($_POST["username"])<4){
			$this->error("用户名太短！");
		}
		if(strlen($_POST["password"])<4){
			$this->error("密码太短！");
		}
		$dao	=	D("User");
		$user	=	$dao->create();
		if (!$user){
			$this->error($dao->getError());
		}
		$user->password	=	md5($_POST["password"]);
		$user->status = 1;
		if($userId = $dao->add($user)){
			$setDao = D('Groupuser');
			$map['userId'] = $userId;
			$map['groupId'] = C("REG_GROUP");
			$setDao->add($map);
			$this->ajaxReturn('','注册成功',1);
		}else{
			$this->ajaxReturn($vo,$dao->getError().'注册失败！',0);
		}
	}

	public function logout(){
		if(C('PASSPORT')){
			$this->redirect('pwlogout','Passport_'.C('PASSPORT_TYPE'));
		}
		Session::clear();
		$this->redirect("index","Index");
		if(isset($_SESSION[C('USER_AUTH_KEY')])) {
			unset($_SESSION[C('USER_AUTH_KEY')]);
			unset($_SESSION['administrator']);
			unset($_SESSION['userInfo']);
			unset($_SESSION['isAdmin']);
			$this->redirect("index","Index");
		}else {
			$this->assign('error', '已经登出！');
			$this->forward();
		}
	}

	function checkemail() {
		$num = D("User")->count('email="'.trim($_POST['email']).'"');
		if ( $num >0 ) {
			echo false;
		}else{
			echo true;
		}
	}
	function checkusername() {
		$num = D("User")->count('username="'.trim($_POST['username']).'"');
		if ( $num >0 ) {
			echo false;
		}else{
			echo true;
		}
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