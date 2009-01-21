<?php
if (!defined('THINK_PATH')) exit();

//载入网站配置
$web_config	=	require 'web_config.php';

//载入数据库配置
$db_config = require 'db_config.php';

//设定项目配置
$array	=	array(
/* SESSION 设定 */
'SESSION_TYPE'		=>	'File',
'SESSION_EXPIRE'	=>	'300000',
'SESSION_TABLE'		=>	'thinkcms_session',
/* 调试配置 */
'DB_FIELDS_CACHE'	=>	false,
'TMPL_CACHE_ON'		=>	false,
'DEBUG_MODE'		=>	true,
/* 项目配置 */
'ROUTER_ON'			=>	true,
'DATA_RESULT_TYPE'	=>	1,
'THINK_PLUGIN_ON'	=>	true,
'DEFAULT_MODULE'	=>	'Index',
'USER_AUTH_ON'      =>  false,
'USER_AUTH_KEY'		=>	'uid',
'USER_UPLOADS'		=>	'./UserUploads/',
'COMPONENT_DEPR'    =>  '_',
/* 语言时区设置 */
'LANG_SWITCH_ON'		=>	false,	 // 默认关闭多语言包功能
'DEFAULT_LANGUAGE'		=>	'zh-cn',	 // 默认语言
'TIME_ZONE'				=>	'PRC',		 // 默认时区
/* 编码设置 */
'TEMPLATE_CHARSET'      =>  'utf-8',        // 模板文件编码
'OUTPUT_CHARSET'        =>  'utf-8',        // 默认输出编码
'DB_CHARSET'            =>  'utf8',        // 数据库编码默认采用utf8
);

//合并输出配置
return array_merge($db_config,$web_config,$array);
?>