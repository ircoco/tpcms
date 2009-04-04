<?php 
// +----------------------------------------------------------------------
// | ThinkPHP                                                             
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.      
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>                                  
// +----------------------------------------------------------------------
// $Id$

// 权限管理
class GroupAction extends BaseAction
{//类定义开始

	// 删除用户组
	function delete()
	{
		$dao=D("Group");
		$pkey=$_GET['id'];
		$akey=split(",",$pkey);
		if (count($akey)<=0){
			$this->error("出错!请选择删除的条目");
		}
		foreach($akey as $key){
			$dao->delete("id=$key");
		}
		$this->assign('jumpUrl',__URL__);
		$this->success("删除成功!");
	}

    // 设置项目权限
    function setApp() 
    {
        $id     = $_POST['groupAppId'];
		$groupId	=	$_POST['groupId'];
		$Group    =   D("Group");
		$Group->delGroupApp($groupId);
		$result = $Group->setGroupApps($groupId,$id);

		if($result===false) {
			$this->error('项目授权失败！');
		}else {
			$this->success('项目授权成功！');
		}
    }
	

	// 读取项目权限
    function app() 
    {
        //读取系统的项目列表
        $dao    =  D("Node");
        $list         =  $dao->findAll('level=1','id,title');
        $appList   =  $dao->getCols($list,'id,title');

        //读取系统组列表
		$Group   =  D("Group");
        $list       =  $Group->findAll('','id,name');
        $groupList  =  $Group->getCols($list,'id,name');
		$this->assign("groupList",$groupList);

        //获取当前用户组项目权限信息
        $groupId =  isset($_GET['groupId'])?$_GET['groupId']:'';
		$groupAppList = array();
		if(!empty($groupId)) {
			$this->assign("selectGroupId",$groupId);
			//获取当前组的操作权限列表
            $selectAppList = $Group->getGroupAppList($groupId);
            $groupAppList = $Group->getCols($selectAppList,'id,id');
		}
		
		$this->assign('groupAppList',$groupAppList);
        $this->assign('appList',$appList);

        $this->display();

        return;
    }

	// 设置模块权限
    function setModule() 
    {
        $id     = $_POST['groupModuleId'];
		$groupId	=	$_POST['groupId'];
        $appId	=	$_POST['appId'];
		$Group    =   D("Group");
		$Group->delGroupModule($groupId,$appId);
		$result = $Group->setGroupModules($groupId,$id);

		if($result===false) {
			$this->error('模块授权失败！');
		}else {
			$this->success('模块授权成功！');
		}
    }

	// 读取模块权限
	function module() 
    {
        $groupId =  $_GET['groupId'];
        $appId  = $_GET['appId'];

		$Group   =  D("Group");
        //读取系统组列表
        $list       =  $Group->findAll('','id,name');
        $groupList  =  $Group->getCols($list,'id,name');
		$this->assign("groupList",$groupList);

        if(!empty($groupId)) {
			$this->assign("selectGroupId",$groupId);
            //读取系统组的授权项目列表
            $list       =  $Group->getGroupAppList($groupId);
            $appList  =  $Group->getCols($list,'id,title');
            $this->assign("appList",$appList);
        }

        $Node    =  D("Node");
        if(!empty($appId)) {
            $this->assign("selectAppId",$appId);
        	//读取当前项目的模块列表
            $list         =  $Node->findAll('level=2 and pid='.$appId,'id,title');
            $moduleList   =  $Node->getCols($list,'id,title');
        }

        //获取当前项目的授权模块信息
		$groupModuleList = array();
		if(!empty($groupId) && !empty($appId)) {
            $selectModuleList = $Group->getGroupModuleList($groupId,$appId);
            $groupModuleList = $Group->getCols($selectModuleList,'id,id');
		}
		
		$this->assign('groupModuleList',$groupModuleList);
        $this->assign('moduleList',$moduleList);
        $this->display();

        return;
    }

	// 设置操作权限
    function setAction() 
    {
        $id     = $_POST['groupActionId'];
		$groupId	=	$_POST['groupId'];
        $moduleId	=	$_POST['moduleId'];
		$Group    =   D("Group");
		$Group->delGroupAction($groupId,$moduleId);
		$result = $Group->setGroupActions($groupId,$id);

		if($result===false) {
			$this->error('操作授权失败！');
		}else {
			$this->success('操作授权成功！');
		}
    }


	// 读取操作权限
    function action() 
    {
        $groupId =  $_GET['groupId'];
        $appId  = $_GET['appId'];
        $moduleId  = $_GET['moduleId'];

		$Group   =  D("Group");
        //读取系统组列表
        $list       =  $Group->findAll('','id,name');
        $groupList  =  $Group->getCols($list,'id,name');
		$this->assign("groupList",$groupList);

        if(!empty($groupId)) {
			$this->assign("selectGroupId",$groupId);
            //读取系统组的授权项目列表
            $list       =  $Group->getGroupAppList($groupId);
            $appList  =  $Group->getCols($list,'id,title');
            $this->assign("appList",$appList);
        }
        if(!empty($appId)) {
            $this->assign("selectAppId",$appId);
        	//读取当前项目的授权模块列表
            $list         =  $Group->getGroupModuleList($groupId,$appId);
            $moduleList   =  $Group->getCols($list,'id,title');
            $this->assign("moduleList",$moduleList);
        }
        $Node    =  D("Node");

        if(!empty($moduleId)) {
            $this->assign("selectModuleId",$moduleId);
        	//读取当前项目的操作列表
            $list         =  $Node->findAll('level=3 and pid='.$moduleId,'id,title');
            $actionList   = $Node->getCols($list,'id,title');
        }


        //获取当前用户组操作权限信息
		$groupActionList = array();
		if(!empty($groupId) && !empty($moduleId)) {
			//获取当前组的操作权限列表
            $selectActionList = $Group->getGroupActionList($groupId,$moduleId);
            $groupActionList = $Group->getCols($selectActionList,'id,id');
		}

		$this->assign('groupActionList',$groupActionList);
        $this->assign('actionList',$actionList);
        $this->display();

        return;
    }

	// 设置组用户
    function setUser() 
    {
        $id     = $_POST['groupUserId'];
		$groupId	=	$_POST['groupId'];
		$Group    =   D("Group");
		$Group->delGroupUser($groupId);
		$result = $Group->setGroupUsers($groupId,$id);
		if($result===false) {
			$this->error('授权失败！');
		}else {
			$this->success('授权成功！');
		}
    }

	// 读取组用户
    function user() 
    {
        //读取系统的用户列表
		//以下三句请根据实际情况进行修改
        $User    =   D("User");
        $list		=	$User->field('uid,username')->findAll();
        $userList	=	$User->getCols($list,'uid,username');

		$Group    =   D("Group");
        $list   =  $Group->field('id,name')->findAll();
        $groupList = $Group->getCols($list,'id,name');
		$this->assign("groupList",$groupList);

        //获取当前用户组信息
        $groupId =  isset($_GET['id'])?$_GET['id']:'';
		$groupUserList = array();
		if(!empty($groupId)) {
			$this->assign("selectGroupId",$groupId);
			//获取当前组的用户列表
            $list = $Group->getGroupUserList($groupId);
            $groupUserList = $Group->getCols($list,'uid,uid');
		}
		$this->assign('groupUserList',$groupUserList);
        $this->assign('userList',$userList);
        $this->display();

        return;
    }

	// 组列表
	function index() 
	{ 
		$Group = D("Group"); 
		$count= $Group->count(); 
 
		import("ORG.Util.Page"); 
		if(!empty($_REQUEST['listRows'])) { 
			$listRows = $_REQUEST['listRows']; 
			}else{ 
			$listRows=20; 
		} 
		$p		= new Page($count,$listRows); 
 		$list	=$Group->order('id desc')->limit($p->firstRow.','.$p->listRows)->findAll(); 
		$page=$p->show(); 
		$this->assign('list',$list); 
		$this->assign('page',$page); 
		$this->display(); 
	}
}//类定义结束
?>