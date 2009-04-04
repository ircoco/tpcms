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
//定义项目名称和路径 默认放在SNS目录下
define('APP_NAME', 'CMS');
define('APP_PATH', './CMS');
define('ROOT_PATH','.');
define('TC_DATA_PATH', './Data');
// 加载框架入口文件
require(THINK_PATH."/ThinkCMS.php");
//实例化一个网站应用实例
$App = new App();
//应用程序初始化
$App->run();
?>