<?php
class PublicAction extends BaseAction {
	public function index(){
		$this->redirect('checklogin');
	}

	public function login(){
		$this->display();
	}

	//登录检测
	public function checklogin(){
		$secure_code	=	C('SECURE_CODE');
		$userName	=	isset($_POST["username"]) ? $_POST["username"] : Cookie::get("username");
		$userPassword	=	isset($_POST["password"]) ? md5($secure_code.md5($_POST["password"])) : Cookie::get("securecode");

		//验证用户信息
		$userDao	=	D('User');

		$user		=	$userDao->find($map);
		//$user = $userDao->findAll();

		//验证成功
		if(md5($secure_code.$user->password) == $userPassword){
			//记录登陆状态
			Session::set('uid',$user->uid);
			Session::set('userInfo',$user);
			Cookie::set('username',$userName,36000000);
			//记住登录
			if($_POST["autologin"] == 'on'){
				Cookie::set("securecode",$userPassword,36000000);
			}
			//exit;
			//更新登录时间
			$userDao->setField('lastLoginTime',time(),"uid=".$user->uid);
			$this->redirect('index','Index');
		//验证失败
		}else{
			//跳转到登陆页面
			$this->assign("error",'登录失败');
			$this->forward();
		}

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
			$this->display();
		}
	}
	public function logout(){
		if(C('PASSPORT')){
			$this->redirect('pwlogout','Passport_'.C('PASSPORT_TYPE'));
		}
		Session::clear();
		Cookie::delete("username");
		Cookie::delete("email");
		Cookie::delete("password");
		Cookie::delete("securecode");
		Cookie::clear();
		$this->redirect("index","Index");
	}
	public function checkemail() {
		$num = D("User")->count('email ="'.trim($_POST['email']).'"');
		if ( $num >0 ) {
			echo false;
		}else{
			echo true;
		}
	}
	public function checkname() {
		$name	=	$_POST['name'];
		//单姓(danxing)和复姓(fuxing)可能不全.收到反馈后我们会继续更新.
		//2008-6-30 增加:官(guan),仝(tong)
		//2008-7-13 增加:代(dai)
		$danxing	=	'赵,钱,孙,李,周,吴,郑,王,冯,陈,褚,卫,蒋,沈,韩,杨,朱,秦,尤,许,何,吕,施,张,孔,曹,严,华,金,魏,陶,姜,戚,谢,邹,喻,柏,水,窦,章,云,苏,潘,葛,奚,范,彭,郎,鲁,韦,昌,马,苗,凤,花,方,俞,任,袁,柳,酆,鲍,史,唐,费,廉,岑,薛,雷,贺,倪,汤,滕,殷,罗,毕,郝,邬,安,常,乐,于,时,傅,皮,卞,齐,康,伍,元,余,卜,顾,孟,平,黄,和,穆,萧,尹,姚,邵,湛,汪,祁,毛,禹,狄,米,贝,明,臧,计,伏,成,戴,谈,宋,茅,庞,熊,纪,舒,屈,项,祝,董,梁,杜,阮,蓝,闵,席,季,麻,强,贾,路,娄,危,江,童,颜,郭,梅,盛,林,刁,钟,徐,邱,骆,高,夏,蔡,田,樊,胡,凌,霍,虞,万,支,柯,昝,管,卢,莫,白,房,裘,缪,干,解,应,宗,丁,宣,贲,邓,郁,单,杭,洪,包,诸,左,石,崔,吉,钮,龚,程,嵇,邢,滑,裴,陆,荣,翁,荀,羊,于,惠,甄,曲,家,封,芮,羿,储,靳,汲,邴,糜,松,井,段,富,巫,乌,焦,巴,弓,牧,隗,山,谷,车,侯,宓,蓬,全,郗,班,仰,秋,仲,伊,宫,宁,仇,栾,暴,甘,钭,历,戎,祖,武,符,刘,景,詹,束,龙,叶,幸,司,韶,郜,黎,蓟,溥,印,宿,白,怀,蒲,邰,从,鄂,索,咸,籍,赖,卓,蔺,屠,蒙,池,乔,阳,郁,胥,能,苍,双,闻,莘,党,翟,谭,贡,劳,逄,姬,申,扶,堵,冉,宰,郦,雍,却,璩,桑,桂,濮,牛,寿,通,边,扈,燕,冀,姓,浦,尚,农,温,别,庄,晏,柴,瞿,阎,充,慕,连,茹,习,宦,艾,鱼,容,向,古,易,慎,戈,廖,庾,终,暨,居,衡,步,都,耿,满,弘,匡,国,文,寇,广,禄,阙,东,欧,殳,沃,利,蔚,越,夔,隆,师,巩,厍,聂,晁,勾,敖,融,冷,訾,辛,阚,那,简,饶,空,曾,毋,沙,乜,养,鞠,须,丰,巢,关,蒯,相,查,后,荆,红,游,竺,权,逮,盍,益,桓,公,官,仝,代';
		$fuxing	=	'万俟,司马,上官,欧阳,夏侯,诸葛,闻人,东方,赫连,皇甫,尉迟,公羊,澹台,公冶,宗政,濮阳,淳于,单于,太叔,申屠,公孙,仲孙,轩辕,令狐,徐离,宇文,长孙,慕容,司徒,司空';
		$d	=	explode(',',$danxing);
		$f	=	explode(',',$fuxing);
		//单姓
		$name_d	=	substr($name,0,3);
		//复姓
		$name_f	=	substr($name,0,6);
		if( in_array($name_d,$d) ){
			return true;
		}else
		if( in_array($name_f,$f) ){
			return true;
		}else{
			return false;
		}
	}
}
?>