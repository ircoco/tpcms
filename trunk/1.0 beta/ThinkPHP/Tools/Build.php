#!/usr/local/php/bin/php
<?php
// 加载配置文件
require "./config.php";
// 加载公共文件
require "./common.php";

echo "
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
";

init();
// 分析命令
if($argc ==1) {
    // 进入交互模式
    begin();
}else{
    // 进入自动模式
    $type = $argv[1];
    $name = $argv[2];
    switch(strtolower($type)) {
        case 'help':
            help();
            break;
        case 'model':
            buildModel($name);
            break;
        case 'action':
            buildAction($name);
            break;
    }
}

?>