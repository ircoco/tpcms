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

/**
 +------------------------------------------------------------------------------
 * ThinkPHP公共文件
 +------------------------------------------------------------------------------
 */
if(version_compare(PHP_VERSION,'5.0.0','<') ) {
    die('ThinkPHP 1.* require PHP > 5.0 !');
}
//记录开始运行时间
$GLOBALS['_beginTime'] = microtime(TRUE);

// ThinkPHP系统目录定义
if(!defined('THINK_PATH')) define('THINK_PATH', dirname(__FILE__));
if(!defined('APP_NAME')) define('APP_NAME', md5(THINK_PATH));
if(!defined('APP_PATH')) define('APP_PATH', dirname(THINK_PATH).'/'.APP_NAME);
if(!defined('RUNTIME_PATH')) define('RUNTIME_PATH',APP_PATH.'/Temp/');

if(is_file(RUNTIME_PATH.'~runtime.php') && filemtime(RUNTIME_PATH.'~runtime.php')>filemtime(THINK_PATH.'/ThinkPHP.php')) {
    // 加载框架核心缓存文件
    // 如果有修改核心文件请删除该缓存
    require RUNTIME_PATH.'~runtime.php';
}else{
    // 加载系统定义文件
    require THINK_PATH."/Common/defines.php";
    // 系统函数库
    require THINK_PATH."/Common/functions.php";
    // 加载编译需要的函数文件
    require THINK_PATH."/Common/runtime.php";
    // 第一次运行检查项目目录结构 如果不存在则自动创建
    if(!is_dir(RUNTIME_PATH)) {
        // 创建项目目录结构
        buildAppDir();
    }

    //加载ThinkPHP基类
    import("Think.Core.Base");
    //加载异常处理类
    import("Think.Exception.ThinkException");
    // 加载日志类
    import("Think.Util.Log");
    //加载Think核心类
    import("Think.Core.App");
    import("Think.Core.Action");
    import("Think.Core.Model");
    import("Think.Core.View");
    // 是否生成核心缓存
    $cache  =   ( !defined('CACHE_RUNTIME') || CACHE_RUNTIME == true );
    if($cache) {
        if(defined('STRIP_RUNTIME_SPACE') && STRIP_RUNTIME_SPACE == false ) {
            $fun    =   'file_get_contents';
        }else{
            $fun    =   'php_strip_whitespace';
        }
        // 生成核心文件的缓存 去掉文件空白以减少大小
        $content     =   $fun(THINK_PATH.'/Common/defines.php');
        $content    .=   $fun(THINK_PATH.'/Common/functions.php');
        $content    .=   $fun(THINK_PATH.'/Lib/Think/Core/Base.class.php');
        $content    .=   $fun(THINK_PATH.'/Lib/Think/Exception/ThinkException.class.php');
        $content    .=   $fun(THINK_PATH.'/Lib/Think/Util/Log.class.php');
        $content    .=   $fun(THINK_PATH.'/Lib/Think/Core/App.class.php');
        $content    .=   $fun(THINK_PATH.'/Lib/Think/Core/Action.class.php');
        $content    .=   $fun(THINK_PATH.'/Lib/Think/Core/Model.class.php');
        $content    .=   $fun(THINK_PATH.'/Lib/Think/Core/View.class.php');
    }
    if(version_compare(PHP_VERSION,'5.2.0','<') ) {
        // 加载兼容函数
        require THINK_PATH.'/Common/compat.php';
        if($cache) {
            $content .=  $fun(THINK_PATH.'/Common/compat.php');
        }
    }
    if($cache) {
        file_put_contents(RUNTIME_PATH.'~runtime.php',$content);
        unset($content);
    }
}
// 记录加载文件时间
$GLOBALS['_loadTime'] = microtime(TRUE);
?>