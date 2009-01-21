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

//加载配置文件
require './config.php';
//加载ThinkPHP基类
require THINK_PATH."/Common/defines.php";
// 系统函数库
require THINK_PATH."/Common/functions.php";
//加载ThinkPHP基类
import("Think.Core.Base");
//加载Think核心类
import("Think.Util.Config");
import("Think.Db.Db");
// 数据库连接信息设置
C($config);

function init() {
    mk_dir(APP_PATH.'Lib/Model/');
    mk_dir(APP_PATH.'Lib/Action/');
}

function buildModel($name='') {
    if(empty($name)) {
        $db =   DB::getInstance();
        $tables = $db->getTables(C('DB_NAME'));
        foreach ($tables as $table){
            $table    =   str_replace(C('DB_PREFIX'),'',$table);
            buildModel($table);
        }
    }else{
        $name   =   ucwords($name);
        echo '正在生成'.$name.'Model类...';
        $filename = APP_PATH.'Lib/Model/'.$name.'Model.class.php';
        if(!file_exists($filename)) {
            $content =   "<?php \n";
            $content .= "class ".$name."Model extends Model \n{\n";
            $content .= "}\n?>";
            if(file_put_contents($filename,$content)){
                echo "...Complete\n";
            }else{
                echo "...Fail\n";
            };
        }else{
            echo "...Exists\n";
        }
    }
}

function buildAction($name='') {
    if(empty($name)) {
        $db =   DB::getInstance();
        $tables = $db->getTables(C('DB_NAME'));
        foreach ($tables as $table){
            $table    =   str_replace(C('DB_PREFIX'),'',$table);
            buildAction($table);
        }
    }else{
        $name   =   ucwords($name);
        echo '正在生成'.$name.'Action类...';
        $filename = APP_PATH.'Lib/Action/'.$name.'Action.class.php';
        if(!file_exists($filename)) {
            $content =   "<?php \n";
            $content .= "class ".$name."Action extends Action \n{\n";
            $content .= "}\n?>";
            if(file_put_contents($filename,$content)){
                echo "...Complete\n";
            }else{
                echo "...Fail\n";
            };
        }else{
            echo "...Exists\n";
        }
    }
}
function begin() {
echo "
+------------------------
| [ 1 ] 生成Model
| [ 2 ] 生成Action
| [ 0 ] 退出
+------------------------
输入数字选择:";
    $number = trim(fgets(STDIN,256));
    //fscanf(STDIN, "%d\n", $number); // 从 STDIN 读取数字
    switch($number) {
    case 0:
        break;
    case 1:
        echo "输入Model名称[例如 User，留空生成当前数据库的全部Model类 ]:";
        $model = trim(fgets(STDIN,256));
        if(strpos($model,',')) {
            echo "批量生成Model...\n";
            $models = explode(',',$model);
            foreach ($models as $model){
                buildModel($model);
            }
        }else{
            // 生成指定的Model类
            buildModel($model);
        }
        begin();
        break;
    case 2:
        // 生成指定的Action类
        echo "输入Action名称[例如 User ]:";
        $action = trim(fgets(STDIN,256));
        buildAction($action);
        begin();
        break;
    default:
        begin();
    }
}

function help() {
    echo "
命令格式：php Build type <...>
example:
build model <name> 生成Model类
build action <name> 生成Action类
build help 帮助
    ";
    exit;
}
?>