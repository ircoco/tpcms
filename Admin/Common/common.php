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
function clearCMSCache()
{
	import("ORG.Io.Dir");
	Dir::del(ROOT_PATH."/Data/CMS_Runtime/");
	Dir::del(ROOT_PATH."/Data/Cache/");
	Dir::del(ROOT_PATH."/Data/Temp/");
}
function toDate($time,$format='Y-m-d H:i:s')
{
	if( empty($time)) {
		return '';
	}
	$format = str_replace('#',':',$format);
	return date(auto_charset($format),$time);
}

function getPageType($type)
{
	switch($type) {
		case 0:
			$showText = '首页显示';
			break;
		case 1:
		default:
			$showText = '普通页面';
	}
	return $showText;
}

function getUsername($uid)
{
	$dao = D('User');
	$username	=	$dao->find('uid='.$uid,'username');
	return $username->username;
}

function getGroupName($id) 
{
    if($id==0) {
    	return '无上级组';
    }
	if(Session::is_set('groupName')) {
		$name	=	Session::get('groupName');
		return $name[$id];
	}
	$Group	=	D("Group");
	$list	=	$Group->field('id,name')->findAll();
	$nameList	=	$list->getCol('id,name');
	$name	=	$nameList[$id];
	Session::set('groupName',$nameList);
    return $name;

}

function getMenuname($menuId)
{
	$dao = D('Menu');
	$menuname	=	$dao->find('id='.$menuId,'*','mcnname');
	return $menuname->mcnname;
}

function getADPlace($place)
{
	switch ($place) {
		case 'side':
			$showPlace = '边栏广告';
			break;
		case 'content':
			$showPlace = '文章内容广告';
			break;
		case 'header':
			$showPlace = '顶部广告';
			break;
		case 'search':
			$showPlace = '搜索';
			break;
		case 'other':
			$showPlace = '其它位置';
			break;
	}
	return $showPlace;
}

function getADType($type)
{
	switch ($type) {
		case 'pic':
			$showType = '图片';
			break;
		case 'word':
			$showType = '文字';
			break;
		case 'code':
			$showType = '代码';
			break;
	}
	return $showType;
}

function getStatus($status,$imageShow=true)
{
	switch($status) {
		case 0:
			$showText   = '禁用';
			$showImg    = '<IMG SRC="'.APP_PUBLIC_URL.'/Images/locked.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="禁用">';
			break;
		case 1:
		default:
			$showText   =   '正常';
			$showImg    =   '<IMG SRC="'.APP_PUBLIC_URL.'/Images/ok.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="正常">';

	}
	return ($imageShow===true)? auto_charset($showImg) : $showText;
}

function getAdmenu($admenu,$imageShow=false)
{
	switch($admenu) {
		case 0:
			$showText   = '否';
			$showImg    = '<IMG SRC="'.APP_PUBLIC_URL.'/Images/locked.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="否">';
			break;
		case 1:
		default:
			$showText   =   '是';
			$showImg    =   '<IMG SRC="'.APP_PUBLIC_URL.'/Images/ok.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="是">';

	}
	return ($imageShow===true)? auto_charset($showImg) : $showText;
}

function getYesNo($words,$imageShow=false)
{
	switch($words) {
		case 0:
			$showText   = '否';
			$showImg    = '<IMG SRC="'.APP_PUBLIC_URL.'/Images/locked.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="否">';
			break;
		case 1:
		default:
			$showText   =   '是';
			$showImg    =   '<IMG SRC="'.APP_PUBLIC_URL.'/Images/ok.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="是">';

	}
	return ($imageShow===true)? auto_charset($showImg) : $showText;
}

function getModuleName($mid)
{
	$dao = D('Module');
	$mcnname	=	$dao->find('id='."'$mid'",'modcnname');
	//dump($mcnname);
	return $mcnname->modcnname;
}

function byte_format($input, $dec=0)
{
	$prefix_arr = array("B", "K", "M", "G", "T");
	$value = round($input, $dec);
	$i=0;
	while ($value>1024)
	{
		$value /= 1024;
		$i++;
	}
	$return_str = round($value, $dec).$prefix_arr[$i];
	return $return_str;
}

function getTemplateDesrc($template)
{
	$content = file_get_contents(BLOG_PATH."/Tpl/".$template."/readme.txt");
	return str_replace(array("\r","\n","'"),array("","<br />","\\'"),$content);
}
?>