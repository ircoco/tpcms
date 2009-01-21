<?php
class PHPWindAction extends Action {
	var $uid;
	function _initialize(){
		$this->uid	=	$_SESSION['uid'];
		if(C('PASSPORT')!=1){
			$this->error('通行证没有开启！');
			exit;
		}
		$dao = D('Menu');
		$menu	=	$dao->findAll('status>0','*','orderId desc');
		$this->assign("menu",$menu);

		$dao = D('Pages');
		$pages	=	$dao->findAll('status>0','pagesId,pname,pcnname,orderId,status','orderId desc');
		$this->assign("pages",$pages);
	}

	public function index(){
		$this->redirect('checklogin');
	}

	public function pwlogin(){
		if(C('PASSPORT')==1){
			$this->display('pwlogin');
		}else{
			$this->error('通行证没有开启！');
		}
	}

	//登录检测
	public function checklogin(){
		//获取帐号，密码
		$secure_code	=	C('SECURE_CODE');
		$username	=	isset($_POST["username"]) ? $_POST["username"] : Cookie::get("username");
		$userPassword	=	isset($_POST["password"]) ? md5($secure_code.md5($_POST["password"])) : Cookie::get("securecode");
		//验证用户信息
		$userDao	=	D('User');
		$user		=	$userDao->find($map);
		//验证成功
		if(md5($secure_code.$user->password) == $userPassword){
			//记录登陆状态
			Session::set('uid',$user->uid);
			Session::set('userInfo',$user);
			Cookie::set('username',$username,36000000);
			//记住登录
			if($_POST["autologin"] == 'on'){
				Cookie::set("securecode",$userPassword,36000000);
			}
			//exit;
			//更新用户缓存
			//setUserInfo($user->uid);
			//更新登录时间
			$userDao->setField('lastLoginTime',time(),"uid=".$user->uid);

			/*	PW 通行证整合	*/

			$userdb=array(
			'username' => $user->username,
			'password' => $user->password,
			'email'	   => $user->email,
			'time'	   => time(),
			);
			if(isset($_POST['forward']) && !empty($_POST['forward'])){
				$forward	=	base64_decode($_POST['forward']);
			}else{
				$forward	=	C('SITE_HOST').'/index.php/';
			}
			$bbs_url	=	C('BBS_URL');
			$authData		=	$this->PWUserEncode($userdb);
			$verify		=	md5("login".$authData.$forward.C('PASSPORT_KEY'));
			header("Location:".$bbs_url."passport_client.php?action=login&userdb=".rawurlencode($authData)."&forward=".rawurlencode($forward)."&verify=".rawurlencode($verify));

			/**/
			//验证失败
		}else{
			//跳转到登陆页面
			$this->assign("error",'登录失败');
			$this->forward();
		}

	}
	
	public function pwlogout(){
		$userId	=	$_SESSION['uid'];
		Session::clear();
		Cookie::delete("username");
		Cookie::delete("password");
		Cookie::clear();
		/* PW整合 */
		//$userId	=	1;
		$user	=	D('User')->find($userId);
		$userdb	=	array(
		'username' => $user->username,
		'password' => $user->password,
		'time'	   => time(),
		);

		$forward	=	C('SITE_HOST');
		$bbs_url	=	C('BBS_URL');

		$authData		=	$this->PWUserEncode($userdb);
		$verify		=	md5("quit".$authData.$forward.C('PASSPORT_KEY'));
		header("Location:".$bbs_url."passport_client.php?action=quit&userdb=".rawurlencode($authData)."&forward=".rawurlencode($forward)."&verify=".rawurlencode($verify));
		/**/
	}

	public function checkEmail() {
		$num = D("User")->count('email ="'.trim($_POST['email']).'"');
		if ( $num >0 ) {
			echo false;
		}else{
			echo true;
		}
	}

	public function checkusername() {
		$num = D("User")->count('username ="'.trim($_POST['username']).'"');
		if ( $num >0 ) {
			echo false;
		}else{
			echo true;
		}
	}

	/*	PW通行证验证加密函数 */
	protected function PWStrEncode($string,$action='ENCODE'){
		$key	=substr(md5($_SERVER['HTTP_USER_AGENT'].C('PASSPORT_KEY')),8,18);
		$string	=$action == 'ENCODE' ? $string : base64_decode($string);
		$len	=strlen($key);
		$code	='';
		for($i=0; $i<strlen($string); $i++){
			$k		= $i % $len;
			$code  .= $string[$i] ^ $key[$k];
		}
		$code = $action == 'DECODE' ? $code : base64_encode($code);
		return $code;
	}

	/*	PW通行证用户数据加密函数 */
	protected function PWUserEncode($userdb) {
		$userdb_encode='';
		foreach($userdb as $key=>$val){
			$userdb_encode .= $userdb_encode ? "&$key=$val" : "$key=$val";
		}
		$userdb_encode	=	str_replace('=','',$this->PWStrEncode($userdb_encode));
		return $userdb_encode;
	}
}
?>