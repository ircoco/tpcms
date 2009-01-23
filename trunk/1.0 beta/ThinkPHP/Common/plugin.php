<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// | Modify: yhustc <yhustc@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +----------------------------------------------------------
 * 判断目录是否为空
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function empty_dir($directory)
{
    $handle = opendir($directory);
    while (($file = readdir($handle)) !== false)
    {
        if ($file != "." && $file != "..")
        {
            closedir($handle);
            return false;
        }
    }
    closedir($handle);
    return true;
}

/**
 +----------------------------------------------------------
 * 读取插件
 +----------------------------------------------------------
 * @param string $path 插件目录
 * @param string $app 所属项目名
 +----------------------------------------------------------
 * @return Array
 +----------------------------------------------------------
 */
function get_plugins($path=PLUGIN_PATH,$app=APP_NAME,$ext='.php')
{
    static $plugins = array ();
    if(isset($plugins[$app])) {
        return $plugins[$app];
    }
    // 如果插件目录为空 返回空数组
    if(empty_dir($path)) {
        return array();
    }
    $path = realpath($path);
    $dir = dir($path);
    if($dir) {
        $plugin_files = array();
        while (false !== ($file = $dir->read())) {
            if($file == "." || $file == "..")   continue;
            if(is_dir($path.'/'.$file)){
                    $subdir =  dir($path.'/'.$file);
                    if ($subdir) {
                        while (($subfile = $subdir->read()) !== false) {
                            if($subfile == "." || $subfile == "..")   continue;
                            if (preg_match('/\.php$/', $subfile))
                                $plugin_files[] = "$file/$subfile";
                        }
                        $subdir->close();
                    }
            }else{
                $plugin_files[] = $file;
            }
        }
        $dir->close();

        //对插件文件排序
        if(count($plugin_files)>1) {
            sort($plugin_files);
        }
        $plugins[$app] = array();
        foreach ($plugin_files as $plugin_file) {
            if ( !is_readable($path.'/'.$plugin_file))        continue;
            $plugins[$app][] = $path.'/'.$plugin_file;
        }
       return $plugins[$app];
    }else {
        return array();
    }
}

/**
 +----------------------------------------------------------
 * 动态添加过滤器
 +----------------------------------------------------------
 * @param string $tag 过滤器标签
 * @param string $function 过滤方法名
 * @param integer $priority 执行优先级
 * @param integer $args 参数
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function add_filter($tag,$function,$priority = 10,$args = 1)
{
    static $_filter = array();
    if ( isset($_filter[APP_NAME.'_'.$tag]["$priority"]) ) {
        foreach($_filter[APP_NAME.'_'.$tag]["$priority"] as $filter) {
            if ( $filter['function'] == $function ) {
                return true;
            }
        }
    }
    $_filter[APP_NAME.'_'.$tag]["$priority"][] = array('function'=> $function,'args'=> $args);
    $_SESSION['_filters']   =   $_filter;
    return true;
}

/**
 +----------------------------------------------------------
 * 删除动态添加的过滤器
 +----------------------------------------------------------
 * @param string $tag 过滤器标签
 * @param string $function 过滤方法名
 * @param integer $priority 执行优先级
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function remove_filter($tag, $function_to_remove, $priority = 10) {
    $_filter  = $_SESSION['_filters'];
    if ( isset($_filter[APP_NAME.'_'.$tag]["$priority"]) ) {
        $new_function_list = array();
        foreach($_filter[APP_NAME.'_'.$tag]["$priority"] as $filter) {
            if ( $filter['function'] != $function_to_remove ) {
                $new_function_list[] = $filter;
            }
        }
        $_filter[APP_NAME.'_'.$tag]["$priority"] = $new_function_list;
    }
    $_SESSION['_filters']   =   $_filter;
    return true;
}

/**
 +----------------------------------------------------------
 * 执行过滤器
 +----------------------------------------------------------
 * @param string $tag 过滤器标签
 * @param string $string 参数
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function apply_filter($tag,$string='')
{
    if (!isset($_SESSION['_filters']) ||  !isset($_SESSION['_filters'][APP_NAME.'_'.$tag]) ) {
        return $string;
    }
    $_filter  = $_SESSION['_filters'][APP_NAME.'_'.$tag];
    ksort($_filter);
    $args = array_slice(func_get_args(), 2);
    foreach ($_filter as $priority => $functions) {
        if ( !is_null($functions) ) {
            foreach($functions as $function) {
                if(is_callable($function['function'])) {
                    $args = array_merge(array($string), $args);
                    $string = call_user_func_array($function['function'],$args);
                }
            }
        }
    }
    return $string;
}
?>