<?php
class BaseAction extends Action
{
	//类定义开始
	function _initialize(){
		if(!isset($_SESSION[C('USER_AUTH_KEY')]))
		{
			$this->redirect('login',"Public");
		}else{
			$dao = D('Module');
			$AdModule	=	$dao->findAll();
			$this->assign("AdModule",$AdModule);
		}
	}

	public function saveTag($tags,$id,$module=MODULE_NAME)
	{
		if(!empty($tags) && !empty($id)) {
			$dao = D("Tag");
			$taggedDao   = D("Tagged");
			// 记录已经存在的标签
			$exists_tags  = $taggedDao->getFields("id,tagId","module='{$module}' and recordId='{$id}'");
			$taggedDao->deleteAll("module='{$module}' and recordId='{$id}'");
			$tags = explode(' ',$tags);
			foreach($tags as $key=>$val) {
				$val  = trim($val);
				if(!empty($val)) {
					$tag =  $dao->find("module='{$module}' and name='$val'");
					if($tag) {
						// 标签已经存在
						if(!in_array($tag->id,$exists_tags)) {
							$dao->setInc('count','id='.$tag->id);
						}

					}else {
						// 不存在则添加
						$tag = new stdClass();
						$tag->name =  $val;
						$tag->count  =  1;
						$tag->module   =  $module;
						$result  = $dao->add($tag);
						$tag->id   =  $result;
					}
					// 记录tag关联信息
					$t = new stdClass();
					$t->module   = $module;
					$t->recordId =  $id;
					$t->tagTime  = time();
					$t->tagId  = $tag->id;
					$taggedDao->add($t);
				}
			}
		}
	}

	public function _trigger($vo) {
		if(ACTION_NAME=='insert') {
			// 补充附件表信息
			$dao	=	D("Attach");
			$attach['verify']	=	0;
			$attach['recordId']	=	$vo->aid;
			$dao->save($attach,"verify='".$_SESSION['attach_verify']."'");
		}
		/*$cha=mb_detect_encoding($vo->tags);
		if ($cha!="UTF-8"){
			$vo->tags=iconv($cha,"UTF-8",$vo->tags);
		}
		*/
		$this->saveTag($vo->tags,$vo->aid);
	}

	public function getAttach() {
		//读取附件信息
		$id	=	$_GET['id'];
		$dao = D('Attach');
		$attachs = $dao->findAll("module='".MODULE_NAME."' and recordId='$id'");
		if(count($attachs)>0) {
			//模板变量赋值
			$this->assign("attachs",$attachs);
		}
	}

	/**
     +----------------------------------------------------------
     * 默认上传操作
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	public function upload() {
		if(!empty($_FILES)) {//如果有文件上传
			// 上传附件并保存信息到数据库
			$this->_upload(MODULE_NAME);
			$this->forward();
		}
	}

	/**
     +----------------------------------------------------------
     * 文件上传功能，支持多文件上传、保存数据库、自动缩略图
     +----------------------------------------------------------
     * @access protected 
     +----------------------------------------------------------
     * @param string $module 附件保存的模块名称
     * @param integer $id 附件保存的模块记录号
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	protected function _upload($module='',$recordId='')
	{
		import("ORG.Net.UploadFile");
		$upload = new UploadFile();
		//设置上传文件大小
		$upload->maxSize  = 102400000 ;
		//设置上传文件类型
		$upload->allowExts  = array('jpg','gif','bmp','rar','zip','doc','swf','txt','ppt');
		$upload->savePath =  ROOT_PATH.'/Public/Uploads/';
		if(isset($_POST['_uploadSaveRule'])) {
			//设置附件命名规则
			$upload->saveRule =  $_POST['_uploadSaveRule'];
		}
		if(!empty($_POST['_uploadFileTable'])) {
			//设置附件关联数据表
			$module =  $_POST['_uploadFileTable'];
		}
		if(!empty($_POST['_uploadRecordId'])) {
			//设置附件关联记录ID
			$recordId =  $_POST['_uploadRecordId'];
		}
		if(!empty($_POST['_uploadFileId'])) {
			//设置附件记录ID
			$id =  $_POST['_uploadFileId'];
		}
		if(!empty($_POST['_uploadFileVerify'])) {
			//设置附件验证码
			$verify =  $_POST['_uploadFileVerify'];
		}
		if(!empty($_POST['_uploadUserId'])) {
			//设置附件上传用户ID
			$userId =  $_POST['_uploadUserId'];
		}else {
			$userId = isset($_SESSION[C('USER_AUTH_KEY')])?$_SESSION[C('USER_AUTH_KEY')]:0;
		}
		if(!empty($_POST['_uploadImgThumb'])) {
			//设置需要生成缩略图，仅对图像文件有效
			$upload->thumb =  $_POST['_uploadImgThumb'];
		}
		if(!empty($_POST['_uploadThumbSuffix'])) {
			//设置需要生成缩略图的文件后缀
			$upload->thumbSuffix =  $_POST['_uploadThumbSuffix'];
		}
		if(!empty($_POST['_uploadThumbMaxWidth'])) {
			//设置缩略图最大宽度
			$upload->thumbMaxWidth =  $_POST['_uploadThumbMaxWidth'];
		}
		if(!empty($_POST['_uploadThumbMaxHeight'])) {
			//设置缩略图最大高度
			$upload->thumbMaxHeight =  $_POST['_uploadThumbMaxHeight'];
		}
		// 支持图片压缩文件上传后解压
		if(!empty($_POST['_uploadZipImages'])) {
			$upload->zipImages = true;
		}
		$uploadReplace =  false;
		if(isset($_POST['_uploadReplace']) && 1==$_POST['_uploadReplace']) {
			//设置附件是否覆盖
			$upload->uploadReplace =  true;
			$uploadReplace = true;
		}
		$uploadFileVersion = false;
		if(isset($_POST['_uploadFileVersion']) && 1==$_POST['_uploadFileVersion']) {
			//设置是否记录附件版本
			$uploadFileVersion =  true;
		}
		$uploadRecord  =  true;
		if(isset($_POST['_uploadRecord']) && 0==$_POST['_uploadRecord']) {
			//设置附件数据是否保存到数据库
			$uploadRecord =  false;
		}
		// 记录上传成功ID
		$uploadId =  array();
		$savename = array();
		//执行上传操作
		if(!$upload->upload()) {
			if($this->isAjax() && isset($_POST['_uploadFileResult'])) {
				$uploadSuccess =  false;
				$ajaxMsg  =  $upload->getErrorMsg();
			}else {
				//捕获上传异常
				$this->error($upload->getErrorMsg());
			}
		}else {
			if($uploadRecord) {
				// 附件数据需要保存到数据库
				//取得成功上传的文件信息
				$uploadList = $upload->getUploadFileInfo();
				$remark	 =	 $_POST['remark'];
				//保存附件信息到数据库
				$Attach    = D('Attach');
				//启动事务
				//$Attach->startTrans();
				foreach($uploadList as $key=>$file) {
					//记录模块信息
					$file['module']     =   $module;
					$file['recordId']   =   $recordId?$recordId:0;
					$file['userId']     =   $userId;
					$file['verify']	=	$verify?$verify:'';
					$file['remark']	 =	 $remark[$key]?$remark[$key]:($remark?$remark:'');
					//保存附件信息到数据库
					if($uploadReplace ) {
						if(!empty($id)) {
							$vo  =  $Attach->getById($id);
						}else{
							$vo  =  $Attach->find("module='".$module."' and recordId='".$recordId."'");
						}
						if(is_object($vo)) {
							$vo	=	get_object_vars($vo);
						}
						if(false !== $vo) {
							// 如果附件为覆盖方式 且已经存在记录，则进行替换
							$id	=	$vo[$Attach->getPk()];
							if($uploadFileVersion) {
								// 记录版本号
								$file['version']	 =	 $vo['version']+1;
								// 备份旧版本文件
								$oldfile	=	$vo['savepath'].$vo['savename'];
								if(is_file($oldfile)) {
									if(!file_exists(dirname($oldfile).'/_version/')) {
										mkdir(dirname($oldfile).'/_version/');
									}
									$bakfile	=	dirname($oldfile).'/_version/'.$id.'_'.$vo['version'].'_'.$vo['savename'];
									$result = rename($oldfile,$bakfile);
								}
							}
							// 覆盖模式
							$file['updateTime']	=	time();
							$Attach->save($file,"attid='".$id."'");
							$uploadId[]   = $id;

						}else {
							$file['uploadTime'] =   time();
							$uploadId[] = $Attach->add($file);
						}
					}else {
						//保存附件信息到数据库
						$file['uploadTime'] =   time();
						$uploadId[] =  $Attach->add($file);
					}
					$savename[] =  $file['savename'];
				}
				//提交事务
				//$Attach->commit();
			}
			$uploadSuccess =  true;
			$ajaxMsg  =  '';
		}

		// 判断是否有Ajax方式上传附件
		// 并且设置了结果显示Html元素
		if($this->isAjax() && isset($_POST['_uploadFileResult']) ) {
			// Ajax方式上传参数信息
			$info = Array();
			$info['success']  =  $uploadSuccess;
			$info['message']   = $ajaxMsg;
			//设置Ajax上传返回元素Id
			$info['uploadResult'] =  $_POST['_uploadFileResult'];
			if(isset($_POST['_uploadFormId'])) {
				//设置Ajax上传表单Id
				$info['uploadFormId'] =  $_POST['_uploadFormId'];
			}
			if(isset($_POST['_uploadResponse'])) {
				//设置Ajax上传响应方法名称
				$info['uploadResponse'] =  $_POST['_uploadResponse'];
			}
			if(!empty($uploadId)) {
				$info['uploadId'] = implode(',',$uploadId);
			}
			$info['savename']   = implode(',',$savename);
			$this->ajaxUploadResult($info);
		}
		return ;

	}

	/**
     +----------------------------------------------------------
     * Ajax上传页面返回信息
     +----------------------------------------------------------
     * @access protected 
     +----------------------------------------------------------
     * @param array $info 附件信息
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
	protected function ajaxUploadResult($info)
	{
		// Ajax方式附件上传提示信息设置
		// 默认使用mootools opacity效果
		$show   = '<script language="JavaScript" src="'.WEB_PUBLIC_URL.'/Js/mootools.js"></script><script language="JavaScript" type="text/javascript">'."\n";
		$show  .= ' var parDoc = window.parent.document;';
		$show  .= ' var result = parDoc.getElementById("'.$info['uploadResult'].'");';
		if(isset($info['uploadFormId'])) {
			$show  .= ' parDoc.getElementById("'.$info['uploadFormId'].'").reset();';
		}
		$show  .= ' result.style.display = "block";';
		$show .= " var myFx = new Fx.Style(result, 'opacity',{duration:600}).custom(0.1,1);";
		if($info['success']) {
			// 提示上传成功
			$show .=  'result.innerHTML = "<div style=\"color:#3333FF\"><IMG SRC=\"'.APP_PUBLIC_URL.'/images/ok.gif\" align=\"absmiddle\" BORDER=\"0\"> 文件上传成功！</div>";';
			// 如果定义了成功响应方法，执行客户端方法
			// 参数为上传的附件id，多个以逗号分割
			if(isset($info['uploadResponse'])) {
				$show  .= 'window.parent.'.$info['uploadResponse'].'("'.$info['uploadId'].'","'.$info['savename'].'");';
			}
		}else {
			// 上传失败
			// 提示上传失败
			$show .=  'result.innerHTML = "<div style=\"color:#FF0000\"><IMG SRC=\"'.APP_PUBLIC_URL.'/images/update.gif\" align=\"absmiddle\" BORDER=\"0\"> 上传失败：'.$info['message'].'</div>";';
		}
		$show .= "\n".'</script>';
		$this->assign('_ajax_upload_',$show);
		return ;
	}

	public function download()
	{
		import("ORG.Net.Http");
		$id         =   $_GET['id'];
		$dao        =   D("Attach");
		$attach	    =   $dao->getById($id);
		$filename   =   $attach->savepath.$attach->savename;
		if(is_file($filename)) {
			if(!isset($_SESSION['attach_down_count_'.$id])) {
				// 下载计数
				$dao->setInc('downCount',"attid=".$id);
				$_SESSION['attach_down_count_'.$id]	=	true;
			}
			Http::download($filename,auto_charset($attach->name,'utf-8','gbk'));
		}
	}

	public function delAttach()
	{
		//删除指定记录
		$dao        = D("Attach");
		$id         = $_REQUEST[$dao->getPk()];
		//id 安全验证
		if(!preg_match('/^\d+(\,\d+)?$/',$id)) {
			throw_exception('非法Id');
		}
		$condition = $dao->getPk().' in ('.$id.')';
		$list	=	$dao->findAll($condition,'savename,savepath');
		if($dao->delete($condition)){
			// 删除附件
			foreach ($list as $file){
				if(is_file($file->savepath.$file->savename)) {
					unlink($file->savepath.$file->savename);
				}elseif(is_dir($file->savepath.$file->savename)){
					import("ORG.Io.Dir");
					Dir::del($file->savepath.$file->savename);
				}
			}
			$this->ajaxReturn($id,'删除成功！',1);
		}else {
			$this->error( '删除失败！');
		}
	}

	public function verify()
	{
		$type	 =	 isset($_GET['type'])?$_GET['type']:'gif';
		import("ORG.Util.Image");
		Image::buildImageVerify(4,1,$type);
	}

}//类定义结束
?>