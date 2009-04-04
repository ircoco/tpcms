<?php
// +----------------------------------------------------------------------
// | ThinkCMS
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: jease(QQ:423136965)
// +----------------------------------------------------------------------
// $Id$
// 定义ThinkPHP框架路径
define('THINK_PATH', './ThinkPHP');
define('CMS_PATH','./CMS');
//定义项目名称和路径 默认放在SNS目录下
define('APP_NAME', 'Admin');
define('APP_PATH', './Admin');
define('CMSMODULE_PATH','./CMS/Lib/Module/');
define('ROOT_PATH','.');
// 加载框架入口文件
require(THINK_PATH."/ThinkPHP.php");
//实例化一个网站应用实例
$App = new App();
//应用程序初始化
$App->run();
?>